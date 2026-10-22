#!/usr/bin/env python3
"""Aligns the reel script to the voice-over without a speech recognizer.

1. Find speech segments (separated by pauses) and syllable peaks from the audio envelope.
2. Split the script into phrases at punctuation and estimate their syllables.
3. Dynamic programming matches phrases to segments by syllable count.
4. Words inside a group are placed on the measured syllable peaks.

Output: align.json with a start/end time for every tagged word.
"""
import json
import math
import re
import struct
import sys
import wave

WAV = sys.argv[1]
OUT = sys.argv[2]

# --------------------------------------------------------------- script
# (word, tag or None, syllable override or None)
# One entry per speech segment found in the audio (segment 0 is a start-up blip with no words).
# The mapping was worked out from segment durations and the script order.
PHRASES = [
    [],
    [('ভাই,', 'bhai'), ('আপনার', None), ('diagnostic', None), ('center-এর', None), ('পুরো', None), ('operation', None), ('কি', None), ('এখনো', 'ekhono'), ('আলাদা', None), ('আলাদা', None), ('system-এ', 'system'), ('চলছে?', 'chalche')],
    [('Patient,', 'pat'), ('appointment,', 'app'), ('billing,', 'bil1'), ('sample,', 'sam'), ('lab', 'lab1'), ('report,', None), ('staff—সবকিছু', 'staff', 5), ('একসাথে', 'manage'), ('manage', None), ('করুন', 'manage_end')],
    [('DT', None), ('Medix-এ!', 'medix1'), ('Patient', 'patient2'), ('Registration', 'reg'), ('থেকে', None)],
    [('Barcode', 'barcode')],
    [('Sample', None), ('Tracking,', None), ('Lab', 'labres'), ('Result,', None), ('Billing,', 'bill2'), ('Report,', 'report')],
    [('Attendance,', 'attend'), ('Payroll—', 'payroll')],
    [('সব', 'ek'), ('এক', None), ('platform-এ।', 'platform')],
    [('আর', 'ar1'), ('এখন', None), ('চলছে', None)],
    [('Special', 'special'), ('Offer!', None), ('Regular', 'regular'), ('Price', None), ('১২,০০০', 'n12k', 4)],
    [('টাকা,', None), ('কিন্তু', 'kintu'), ('এখন', None), ('মাত্র', 'matro'), ('৫,০০০', 'n5k', 3), ('টাকা', None)],
    [('per', 'per'), ('month,', None), ('per', None), ('branch!', 'branch')],
    [('Installation', 'installation')],
    [('মাত্র', None), ('২৫,০০০', 'n25k', 4), ('টাকা', None), ('one-time।', 'onetime')],
    [('আর', 'ar2'), ('installation-এর', None), ('পর', None), ('প্রথম', 'prothom'), ('মাস', None), ('সম্পূর্ণ', None), ('FREE!', 'free1'), ('আর', None), ('yearly', 'yearly'), ('payment', None), ('করলে', None)],
    [('পাবেন', 'paben'), ('আরও', None), ('২', None, 2), ('মাস', None)],
    [('FREE!', 'free2')],
    [('DT', 'dtm2'), ('Medix', None, 3), ('আপনার', None), ('diagnostic', 'diag2'), ('center,', None)],
    [('আরও', None), ('smart,', 'smart')],
    [('fast', 'fast'), ('and', None), ('organized.', 'org')],
    [('আজই', 'ajai1'), ('FREE', None), ('Demo', 'demo'), ('নিন!', None)],
    [('DT', 'dtm3'), ('Medix', None, 3), ('A', None), ('Product', 'product'), ('of', None), ('Datatech', None), ('BD', None), ('Ltd.', 'ltd', 3)],
    [('Call', 'call')],
    [('or', None), ('WhatsApp:', None), ('+880', None, 6), ('1575-888775', None, 12)],
    [('Email:', 'email')],
    [('sales@datatechbd.com', None, 10), ('Live', 'live'), ('Demo:', None), ('dt-medix.datatechbd.com', None, 12)],
    [('আজই', 'ajai2'), ('যোগাযোগ', None), ('করুন', None), ('এবং', None), ('আপনার', None), ('diagnostic', 'diag3'), ('center-কে', None), ('আরও', None), ('smart', 'smart2'), ('way-তে', None), ('manage', 'manage2'), ('করুন।', 'end')],
]


BN_V = set('অআইঈউঊঋএঐওঔ')
BN_SIGN = set('ািীুূৃেৈোৌ')
BN_CONS = set('কখগঘঙচছজঝঞটঠডঢণতথদধনপফবভমযরলশষসহড়ঢ়য়ৎ')


def syl(word):
    if re.search('[ঀ-৿]', word):
        # Bangla: count consonant clusters and independent vowels
        n = 0
        prev_cons = False
        has_sign_last = False
        clusters = 0
        i = 0
        w = re.sub('[^ঀ-৿A-Za-z0-9]', '', word)
        # mixed words like 'center-এর' or 'Medix-এ': count latin part separately
        latin = re.findall('[A-Za-z]+', w)
        bn = re.sub('[A-Za-z0-9]', '', w)
        n = sum(latin_syl(x) for x in latin)
        cl = 0
        last_has_sign = False
        j = 0
        while j < len(bn):
            ch = bn[j]
            if ch in BN_V:
                cl += 1
                last_has_sign = True
            elif ch in BN_CONS:
                # start of a cluster unless previous was hasanta
                if not (j > 0 and bn[j - 1] == '্'):
                    cl += 1
                    last_has_sign = False
            elif ch in BN_SIGN:
                last_has_sign = True
            j += 1
        if cl > 1 and not last_has_sign:
            cl -= 1
        return max(1, n + cl)
    return latin_syl(word)


def latin_syl(w):
    w = re.sub('[^A-Za-z]', '', w).lower()
    if not w:
        return 1
    if w in ('dt', 'bd'):
        return 2
    if w == 'ltd':
        return 3
    groups = re.findall('[aeiouy]+', w)
    n = len(groups)
    if w.endswith('e') and n > 1 and not w.endswith(('le', 'ee')):
        n -= 1
    return max(1, n)


# --------------------------------------------------------------- audio
def load_env():
    wf = wave.open(WAV)
    sr, nch, sw, n = wf.getframerate(), wf.getnchannels(), wf.getsampwidth(), wf.getnframes()
    raw = wf.readframes(n)
    samples = struct.unpack('<%dh' % (len(raw) // 2), raw)
    hop = int(sr * 0.01)
    env = []
    for i in range(0, len(samples) - hop, hop):
        seg = samples[i:i + hop * 2]
        e = math.sqrt(sum(s * s for s in seg) / len(seg))
        env.append(20 * math.log10(e + 1))
    return env  # dB per 10 ms


def smooth(x, k):
    out = []
    for i in range(len(x)):
        a, b = max(0, i - k), min(len(x), i + k + 1)
        out.append(sum(x[a:b]) / (b - a))
    return out


def segments(env, thr, min_gap):
    on = [e > thr for e in env]
    segs, start, gap = [], None, 0
    for i, v in enumerate(on):
        if v:
            if start is None:
                start = i
            gap = 0
            last = i
        elif start is not None:
            gap += 1
            if gap >= min_gap:
                segs.append((start, last))
                start = None
    if start is not None:
        segs.append((start, last))
    return segs


def peaks(env_s, a, b, min_sep=7, prom=2.0):
    pk = []
    for i in range(a + 1, b - 1):
        if env_s[i] >= env_s[i - 1] and env_s[i] > env_s[i + 1]:
            lo = min(env_s[max(a, i - 12):i + 1])
            lo2 = min(env_s[i:min(b, i + 13)])
            if env_s[i] - max(lo, lo2) >= prom:
                if pk and i - pk[-1] < min_sep:
                    if env_s[i] > env_s[pk[-1]]:
                        pk[-1] = i
                else:
                    pk.append(i)
    return pk


def main():
    env = load_env()
    mx = max(env)
    thr = mx - 38
    segs = segments(env, thr, 22)
    env_s = smooth(env, 3)
    seg_pk = [peaks(env_s, a, b) for a, b in segs]
    seg_n = [len(p) for p in seg_pk]
    print('segments', len(segs), 'peaks', sum(seg_n))

    ph_syl = [sum((w[2] if len(w) > 2 else syl(w[0])) for w in p) for p in PHRASES]
    print('phrase syllables', sum(ph_syl), ph_syl)

    # Flatten the script and match contiguous word spans to segments by syllable consistency
    flat = [w for p in PHRASES for w in p]
    wsyl = [(w[2] if len(w) > 2 else syl(w[0])) for w in flat]
    punct = [bool(re.search('[,.!?:—।]$', w[0])) or '—' in w[0] for w in flat]
    cum = [0]
    for x in wsyl:
        cum.append(cum[-1] + x)
    use = [k for k in range(len(segs)) if segs[k][1] - segs[k][0] > 30]  # drop the start-up blip
    S = len(use)
    Wn = len(flat)
    target = []
    for k in use:
        dur = (segs[k][1] - segs[k][0]) / 100.0
        target.append(0.5 * (seg_n[k] + dur / 0.19))
    INF = 1e18
    dp = [[INF] * (Wn + 1) for _ in range(S + 1)]
    bk = [[0] * (Wn + 1) for _ in range(S + 1)]
    dp[0][0] = 0
    for j in range(1, S + 1):
        m = target[j - 1]
        for i in range(j - 1, Wn):
            if dp[j - 1][i] >= INF:
                continue
            for e in range(i + 1, min(Wn, i + 40) + 1):
                est = cum[e] - cum[i]
                c = dp[j - 1][i] + (est - m) ** 2 / (m + 3) - (3.0 if punct[e - 1] else 0) + (0.8 if e < Wn and not punct[e - 1] else 0)
                if c < dp[j][e]:
                    dp[j][e] = c
                    bk[j][e] = i
    spans = []
    e = Wn
    for j in range(S, 0, -1):
        i = bk[j][e]
        spans.append((i, e, use[j - 1]))
        e = i
    spans.reverse()
    print('dp cost', dp[S][Wn])
    groups = []
    for (i, e, k) in spans:
        groups.append((i, e, k))
        print('seg %2d %6.2f-%6.2f est %3d target %5.1f  %s' % (k, segs[k][0] / 100, segs[k][1] / 100, cum[e] - cum[i], target[use.index(k)], ' '.join(w[0] for w in flat[i:e])[:70]))

    words = []
    for (i0, e0, k) in groups:
        ws = flat[i0:e0]
        sy = wsyl[i0:e0]
        pks = seg_pk[k]
        t0 = segs[k][0] / 100.0
        t1 = segs[k][1] / 100.0
        total = sum(sy)
        cm = 0
        for w, x in zip(ws, sy):
            if pks:
                idx = min(len(pks) - 1, int(round(cm * len(pks) / total)))
                st = pks[idx] / 100.0 - 0.06
                idx_end = min(len(pks) - 1, max(idx, int(round((cm + x) * len(pks) / total)) - 1))
                en = pks[idx_end] / 100.0 + 0.12
            else:
                st, en = t0, t1
            st = max(t0, st)
            if cm == 0:
                st = t0
            words.append({'word': w[0], 'tag': w[1], 'start': round(st, 3), 'end': round(min(t1, en), 3), 'seg': k})
            cm += x
    tags = {w['tag']: {'start': w['start'], 'end': w['end']} for w in words if w['tag']}
    json.dump({'words': words, 'tags': tags, 'segments': [(a / 100, b / 100) for a, b in segs]}, open(OUT, 'w'), ensure_ascii=False, indent=1)


main()
