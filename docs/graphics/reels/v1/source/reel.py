#!/usr/bin/env python3
"""DT Medix reel 01 (1080x1920, 30 fps) renderer.

Bangla text cannot be shaped by Pillow here, so every text label is rendered once in a
browser (sheets/*.html, white on black), screenshotted, and used as an alpha mask.

Modes:
  python3 reel.py sheets            write sheets/sheet_N.html and manifest.json
  python3 reel.py extract           read $WORK/shots/sheet_N.jpg into $WORK/masks
  python3 reel.py render [a b]      render frames a..b into $WORK/frames (parallel)
  python3 reel.py preview t1 t2 ... write single frames to $WORK/preview
"""
import json
import math
import os
import sys
from multiprocessing import Pool

from PIL import Image, ImageDraw, ImageFilter

W, H, FPS = 1080, 1920, 30
DURATION = 69.2
HERE = os.path.dirname(os.path.abspath(__file__))
WORK = os.environ.get('REEL_WORK', os.environ.get('TMPDIR', '/tmp') + '/reel')
SHOTS_SRC = '/Volumes/Mac Work/Projects/web-artisan/practices/datatechbd.com/public/assets/images/products/dt-medix/screens'
LOGO_DIR = HERE + '/../../../logo/v1/png'
FONT_DIR = '../../../facebook-posts/v1/source/fonts'

NAVY = (10, 20, 40)
BLUE = (11, 99, 206)
SKY = (56, 168, 255)
ORANGE = (232, 89, 12)
ORANGE_L = (255, 150, 64)
GREEN = (22, 160, 100)
GREEN_L = (92, 235, 160)
WHITE = (255, 255, 255)

# ---------------------------------------------------------------- text registry
TXT = {}


def reg(key, text, size, family='bn', fs=None):
    TXT[key] = {'text': text, 'size': size, 'family': family, 'fs': fs or size}


def clean_sub(s):
    for ch in '“”"':
        s = s.replace(ch, '')
    return s.strip()


SUBS = []
for item in json.load(open(HERE + '/subtitles.json')):
    SUBS.append((item['start'], item['end'], clean_sub(item['text'])))
for i, (a, b, s) in enumerate(SUBS):
    reg(f'sub{i}', s, 44, fs=44 if len(s) <= 29 else 36)

for k, t in [('c_patient', 'Patient'), ('c_appointment', 'Appointment'), ('c_billing', 'Billing'), ('c_sample', 'Sample'),
             ('c_lab', 'Lab report'), ('c_staff', 'Staff')]:
    reg(k, t, 50, 'en')
reg('h1a', 'এখনো আলাদা আলাদা', 100, fs=78)
reg('h1b', 'system-এ চলছে?', 104, fs=80)
reg('manage', 'সবকিছু একসাথে', 92)
reg('allin', 'সব এক platform-এ', 88)
reg('tag_dt', 'Patient থেকে Payroll', 52)
for k, t in [('f_patient', 'Patient Registration'), ('f_barcode', 'Barcode Sample Tracking'), ('f_result', 'Lab Result'),
             ('f_billing', 'Billing'), ('f_report', 'Report'), ('f_attend', 'Attendance'), ('f_payroll', 'Payroll')]:
    reg(k, t, 54, 'en')
reg('sp1', 'SPECIAL', 150, 'en')
reg('sp2', 'OFFER', 150, 'en')
reg('regp', 'Regular Price', 60, 'en')
reg('onlyn', 'কিন্তু এখন মাত্র', 84)
reg('bdt', 'টাকা', 100)
reg('bdt_s', 'টাকা', 64)
reg('per', 'per month, per branch', 56, 'en')
reg('off58', '58% OFF', 62, 'en')
reg('inst', 'Installation', 84, 'en')
reg('onetime', 'one-time', 60, 'en')
reg('inst_bn', 'ইনস্টলেশন ফি মাত্র', 66)
reg('free1', 'প্রথম মাস', 90)
reg('freeb', 'FREE', 230, 'en')
reg('afterinst', 'after installation', 56, 'en')
reg('free_bn', 'ইনস্টলেশনের পর সম্পূর্ণ ফ্রি', 58)
reg('yearly', 'YEARLY PAYMENT', 66, 'en')
reg('m2free', '2 MONTHS FREE', 92, 'en')
reg('pay10', 'Pay 10 months, use 12', 56, 'en')
reg('free_s', 'FREE', 42, 'en')
for i in range(1, 13):
    reg(f'mo{i}', str(i), 56, 'en')
reg('tag1', 'আপনার diagnostic center,', 68, fs=52)
reg('smart', 'Smart', 92, 'en')
reg('fast', 'Fast', 92, 'en')
reg('org', 'Organized', 92, 'en')
reg('demo1', 'আজই FREE Demo নিন!', 76, fs=54)
reg('demo2', 'dt-medix.datatechbd.com', 44, 'en')
reg('prod', 'A Product of', 60, 'en')
reg('dbd', 'Datatech BD Ltd.', 92, 'en')
reg('tagline2', 'Software that runs business.', 52, 'en')
reg('k_title', 'যোগাযোগ করুন', 92)
reg('k_call', 'CALL / WHATSAPP', 42, 'en')
reg('k_phone', '+880 1575-888775', 76, 'en')
reg('k_mail', 'EMAIL', 42, 'en')
reg('k_mailv', 'sales@datatechbd.com', 62, 'en')
reg('k_demo', 'LIVE DEMO', 42, 'en')
reg('k_demov', 'dt-medix.datatechbd.com', 54, 'en')
reg('cl1', 'আজই যোগাযোগ করুন', 100, fs=72)
reg('cl2', 'আপনার diagnostic center-কে', 62, fs=46)
reg('cl3', 'আরও smart way-তে manage করুন', 62, fs=46)
reg('btn1', 'FREE Demo নিন', 72)
reg('btn2', 'dt-medix.datatechbd.com', 44, 'en')

DIGIT_ROWS = ['0123', '4567', '89,']  # rendered as rows and split by column gaps
DIGIT_SIZE = 220

FAMILY_CSS = {
    'bn': "'Hind Siliguri','Poppins','Inter',sans-serif",
    'en': "'Poppins','Hind Siliguri','Inter',sans-serif",
}


def write_sheets():
    os.makedirs(HERE + '/sheets', exist_ok=True)
    rows = []
    for k, v in TXT.items():
        rows.append((k, v['text'], v['size'], v['family'], 700, 0, v['fs']))
    for i, r in enumerate(DIGIT_ROWS):
        rows.append((f'digits{i}', r, DIGIT_SIZE, 'en', 700, 26, DIGIT_SIZE))
    sheets, cur, y = [], [], 10
    manifest = {}
    for key, text, size, fam, weight, ls, fs in rows:
        rh = int(size * 1.9) + 20
        if y + rh > 940 and cur:
            sheets.append(cur)
            cur, y = [], 10
        cur.append((key, text, fs, fam, weight, ls, y, rh))
        manifest[key] = {'sheet': len(sheets), 'y': y, 'h': rh}
        y += rh
    sheets.append(cur)
    for n, rowsn in enumerate(sheets):
        body = ''.join(
            f'<div style="top:{y}px;height:{rh}px;line-height:{rh}px;font-size:{size}px;font-family:{FAMILY_CSS[fam]};letter-spacing:{ls}px">{text}</div>'
            for key, text, size, fam, weight, ls, y, rh in rowsn)
        html = f"""<!doctype html><html><head><meta charset="utf-8"><title>sheet {n}</title><style>
@font-face{{font-family:'Poppins';font-weight:700;src:url({FONT_DIR}/poppins-latin-700.woff2) format('woff2')}}
@font-face{{font-family:'Hind Siliguri';font-weight:700;src:url({FONT_DIR}/hind-siliguri-bengali-700.woff2) format('woff2')}}
@font-face{{font-family:'Inter';font-weight:600;src:url({FONT_DIR}/inter-latin-600-normal.woff2) format('woff2')}}
*{{margin:0;padding:0}}html,body{{background:#000;width:800px;height:964px;overflow:hidden}}
div{{position:absolute;left:20px;color:#fff;font-weight:700;white-space:nowrap}}
</style></head><body>{body}</body></html>"""
        open(f'{HERE}/sheets/sheet_{n}.html', 'w').write(html)
    json.dump({'count': len(sheets), 'items': manifest}, open(HERE + '/sheets/manifest.json', 'w'), indent=1)
    print('sheets', len(sheets), 'items', len(manifest))


def extract_masks():
    man = json.load(open(HERE + '/sheets/manifest.json'))
    os.makedirs(WORK + '/masks', exist_ok=True)
    shots = {}
    for k, m in man['items'].items():
        n = m['sheet']
        if n not in shots:
            shots[n] = Image.open(f'{WORK}/shots/sheet_{n}.jpg').convert('L')
        crop = shots[n].crop((0, m['y'], shots[n].width, m['y'] + m['h']))
        bbox = crop.point(lambda v: 255 if v > 40 else 0).getbbox()
        if not bbox:
            print('EMPTY', k)
            continue
        bx = (max(0, bbox[0] - 3), max(0, bbox[1] - 3), min(crop.width, bbox[2] + 3), min(crop.height, bbox[3] + 3))
        mask = crop.crop(bx)
        if bbox[2] > shots[n].width - 25:
            print('WARN wide', k, bbox)
        if k.startswith('digits'):
            mask.save(f'{WORK}/masks/{k}.png')
            continue
        mask.save(f'{WORK}/masks/{k}.png')
    # split digit rows into glyph masks
    glyphs = {}
    for i, row in enumerate(DIGIT_ROWS):
        im = Image.open(f'{WORK}/masks/digits{i}.png')
        cols = [im.crop((x, 0, x + 1, im.height)).point(lambda v: 255 if v > 40 else 0).getbbox() is not None for x in range(im.width)]
        runs, start = [], None
        for x, on in enumerate(cols + [False]):
            if on and start is None:
                start = x
            if not on and start is not None:
                runs.append((start, x))
                start = None
        if len(runs) != len(row):
            print('WARN digit split', i, len(runs), len(row))
        for ch, (a, b) in zip(row, runs):
            g = im.crop((a, 0, b, im.height))
            gb = g.getbbox()
            glyphs[ch] = g
    for ch, g in glyphs.items():
        g.save(f'{WORK}/masks/g_{"comma" if ch == "," else ch}.png')
    print('masks', len(os.listdir(WORK + '/masks')))


# ---------------------------------------------------------------- drawing helpers
_masks, _scaled, _rr, _imgs, _glow = {}, {}, {}, {}, {}


def mask(key):
    if key not in _masks:
        _masks[key] = Image.open(f'{WORK}/masks/{key}.png').convert('L')
    return _masks[key]


def clamp(x, a=0.0, b=1.0):
    return a if x < a else b if x > b else x


def prog(t, a, b):
    return clamp((t - a) / (b - a))


def e_out(x):
    return 1 - (1 - clamp(x)) ** 3


def e_io(x):
    x = clamp(x)
    return x * x * (3 - 2 * x)


def e_back(x):
    x = clamp(x)
    c1 = 1.70158
    c3 = c1 + 1
    return 1 + c3 * (x - 1) ** 3 + c1 * (x - 1) ** 2


def paste_mask(img, color, x, y, m, alpha=1.0):
    x, y = int(round(x)), int(round(y))
    if alpha <= 0.01:
        return
    if alpha < 0.99:
        m = m.point(lambda v: int(v * alpha))
    x0, y0 = max(x, 0), max(y, 0)
    x1, y1 = min(x + m.width, W), min(y + m.height, H)
    if x1 <= x0 or y1 <= y0:
        return
    if (x0, y0, x1, y1) != (x, y, x + m.width, y + m.height):
        m = m.crop((x0 - x, y0 - y, x1 - x, y1 - y))
    img.paste(color, (x0, y0, x1, y1), m)


def text(img, key, cx, cy, color=WHITE, scale=1.0, alpha=1.0, anchor='c'):
    m = mask(key)
    s = round(scale, 2)
    if s != 1.0:
        k = (key, s)
        if k not in _scaled:
            _scaled[k] = m.resize((max(1, int(m.width * s)), max(1, int(m.height * s))), Image.BILINEAR)
        m = _scaled[k]
    if anchor == 'c':
        x, y = cx - m.width / 2, cy - m.height / 2
    elif anchor == 'l':
        x, y = cx, cy - m.height / 2
    else:
        x, y = cx - m.width, cy - m.height / 2
    paste_mask(img, color, x, y, m, alpha)
    return m.width, m.height


def text_w(key, scale=1.0):
    return mask(key).width * scale


def rr(img, box, r, fill, alpha=1.0):
    x0, y0, x1, y1 = [int(round(v)) for v in box]
    w, h = x1 - x0, y1 - y0
    if w < 2 or h < 2 or alpha <= 0.01:
        return
    r = int(min(r, w / 2, h / 2))
    k = (w, h, r)
    if k not in _rr:
        m = Image.new('L', (w * 3, h * 3), 0)
        ImageDraw.Draw(m).rounded_rectangle((0, 0, w * 3 - 1, h * 3 - 1), r * 3, fill=255)
        _rr[k] = m.resize((w, h), Image.LANCZOS)
        if len(_rr) > 400:
            _rr.pop(next(iter(_rr)))
    paste_mask(img, fill, x0, y0, _rr[k], alpha)


def circle(img, cx, cy, r, fill, alpha=1.0):
    rr(img, (cx - r, cy - r, cx + r, cy + r), r, fill, alpha)


def glow(img, cx, cy, r, color, alpha):
    if alpha <= 0.01:
        return
    k = (int(r), color)
    if k not in _glow:
        s = int(r * 2)
        m = Image.new('L', (s, s), 0)
        ImageDraw.Draw(m).ellipse((s * 0.2, s * 0.2, s * 0.8, s * 0.8), fill=255)
        _glow[k] = m.filter(ImageFilter.GaussianBlur(r * 0.22))
    m = _glow[k]
    paste_mask(img, color, cx - m.width / 2, cy - m.height / 2, m, alpha)


def screen(name, width):
    k = (name, int(width))
    if k not in _imgs:
        im = Image.open(f'{SHOTS_SRC}/{name}.jpg').convert('RGB')
        h = int(im.height * width / im.width)
        _imgs[k] = im.resize((int(width), h), Image.BILINEAR)
    return _imgs[k]


def screen_card(img, name, cx, top, width, alpha=1.0, radius=34):
    im = screen(name, width)
    x = int(cx - im.width / 2)
    y = int(top)
    rr(img, (x - 8, y - 8, x + im.width + 8, y + im.height + 8), radius + 6, (255, 255, 255), 0.22 * alpha)
    m = Image.new('L', im.size, 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, im.width - 1, im.height - 1), radius, fill=255)
    m = m.resize((im.width, im.height))
    if alpha < 0.99:
        m = m.point(lambda v: int(v * alpha))
    x0, y0 = max(x, 0), max(y, 0)
    x1, y1 = min(x + im.width, W), min(y + im.height, H)
    if x1 > x0 and y1 > y0:
        img.paste(im.crop((x0 - x, y0 - y, x1 - x, y1 - y)), (x0, y0), m.crop((x0 - x, y0 - y, x1 - x, y1 - y)))
    return im.height


_logo = {}


def logo(img, name, cx, cy, width, alpha=1.0):
    k = (name, int(width))
    if k not in _logo:
        im = Image.open(f'{LOGO_DIR}/{name}.png').convert('RGBA')
        h = int(im.height * width / im.width)
        _logo[k] = im.resize((int(width), h), Image.LANCZOS)
    im = _logo[k]
    a = im.split()[3]
    if alpha < 0.99:
        a = a.point(lambda v: int(v * alpha))
    x, y = int(cx - im.width / 2), int(cy - im.height / 2)
    x0, y0 = max(x, 0), max(y, 0)
    x1, y1 = min(x + im.width, W), min(y + im.height, H)
    if x1 > x0 and y1 > y0:
        img.paste(im.convert('RGB').crop((x0 - x, y0 - y, x1 - x, y1 - y)), (x0, y0), a.crop((x0 - x, y0 - y, x1 - x, y1 - y)))


def number(img, value_str, cx, cy, color, scale=1.0, alpha=1.0):
    """Draw a string of digits and commas using the big digit glyph masks."""
    glyphs = []
    for ch in value_str:
        name = 'g_comma' if ch == ',' else f'g_{ch}'
        glyphs.append(mask(name))
    pad = 10 * scale
    widths = [g.width * scale for g in glyphs]
    total = sum(widths) + pad * (len(glyphs) - 1)
    x = cx - total / 2
    h = max(g.height for g in glyphs) * scale
    for g, w in zip(glyphs, widths):
        s = round(scale, 2)
        gm = g if s == 1.0 else g.resize((max(1, int(g.width * s)), max(1, int(g.height * s))), Image.BILINEAR)
        ch_ = value_str[glyphs.index(g)] if False else None
        by = cy - h / 2 + (h - gm.height) * (1.0 if gm.height < h * 0.5 else 0.5)
        paste_mask(img, color, x, by if gm.height >= h * 0.5 else cy + h / 2 - gm.height, gm, alpha)
        x += w + pad
    return total, h


def pill_text(img, key, cx, cy, fill, color=WHITE, scale=1.0, alpha=1.0, padx=44, pady=22):
    m = mask(key)
    w, h = m.width * scale, m.height * scale
    rr(img, (cx - w / 2 - padx, cy - h / 2 - pady, cx + w / 2 + padx, cy + h / 2 + pady), (h + 2 * pady) / 2, fill, alpha)
    text(img, key, cx, cy, color, scale, alpha)


# ---------------------------------------------------------------- background
_bg = None


def background():
    global _bg
    if _bg is None:
        ramp = Image.linear_gradient('L').resize((W, H))
        top = Image.new('RGB', (W, H), (8, 16, 36))
        bot = Image.new('RGB', (W, H), (12, 72, 170))
        _bg = Image.composite(bot, top, ramp)
    return _bg.copy()


PARTS = [((i * 197) % W, (i * 331) % H, 6 + (i * 7) % 14, 0.8 + (i % 5) * 0.35) for i in range(18)]


def particles(img, t):
    for i, (x, y, r, sp) in enumerate(PARTS):
        yy = (y - t * 22 * sp) % (H + 80) - 40
        xx = x + math.sin(t * 0.6 * sp + i) * 26
        circle(img, xx, yy, r, (255, 255, 255), 0.07)


def vis(t, a, b, fin=0.3, fout=0.3):
    if t < a or t > b + fout:
        return 0.0
    return clamp((t - a) / fin) * (1 - clamp((t - b) / fout)) if fout > 0 else clamp((t - a) / fin)


# ---------------------------------------------------------------- scenes
# Every time below comes from align.json (word times measured against the voice-over).
_AL = json.load(open(HERE + '/align.json'))['tags']


def AN(tag, off=0.0):
    return _AL[tag]['start'] + off


def EN(tag, off=0.0):
    return _AL[tag]['end'] + off


CARDS = [('c_patient', (270, 640), BLUE), ('c_appointment', (800, 600), SKY), ('c_billing', (300, 900), ORANGE),
         ('c_sample', (790, 950), GREEN), ('c_lab', (260, 1170), (140, 90, 220)), ('c_staff', (800, 1200), (230, 60, 110))]
CHIP_DEFS = [('c_patient', 'pat', BLUE), ('c_appointment', 'app', SKY), ('c_billing', 'bil1', ORANGE), ('c_sample', 'sam', GREEN),
             ('c_lab', 'lab1', (140, 90, 220)), ('c_staff', 'staff', (230, 60, 110))]
FEAT_DEFS = [('patient2', 'patients', 'f_patient'), ('barcode', 'sample-label', 'f_barcode'), ('labres', 'result-entry', 'f_result'),
             ('bill2', 'reception', 'f_billing'), ('report', 'report-template', 'f_report'), ('attend', 'attendance-month', 'f_attend'),
             ('payroll', 'payslip', 'f_payroll')]
MOSAIC = ['patients', 'sample-label', 'result-entry', 'reception', 'report-template', 'attendance-month', 'payslip', 'worklist', 'orders']


def scene_hook(img, t):
    end = EN('chalche') + 0.1
    a = vis(t, 0.0, end, 0.4, 0.5)
    if a <= 0:
        return
    conv = e_io(prog(t, end - 0.3, end + 0.7))
    for i, (key, (px, py), col) in enumerate(CARDS):
        x = px + math.sin(t * 1.3 + i * 1.7) * 22
        y = py + math.cos(t * 1.1 + i * 2.1) * 20
        x = x + (540 - x) * conv
        y = y + (780 - y) * conv
        s = 1.0 - 0.35 * conv
        w, h = 330 * s, 120 * s
        al = a * e_out(prog(t, AN('bhai') + i * 0.35, AN('bhai') + 0.5 + i * 0.35))
        rr(img, (x - w / 2, y - h / 2, x + w / 2, y + h / 2), 34 * s, (255, 255, 255), 0.13 * al)
        rr(img, (x - w / 2, y - h / 2, x - w / 2 + 16 * s, y + h / 2), 8 * s, col, 0.95 * al)
        text(img, key, x + 10 * s, y, WHITE, 0.82 * s, al)
    # while the speaker says "diagnostic center-er puro operation ki"
    p0 = prog(t, AN('bhai') + 0.3, AN('bhai') + 0.8) * (1 - prog(t, AN('ekhono') - 0.15, AN('ekhono') + 0.1))
    if p0 > 0:
        text(img, 'tag1', 540, 380, WHITE, 1.0, a * p0)
    ph = prog(t, AN('ekhono'), AN('ekhono') + 0.45)
    if ph > 0:
        sc = 0.7 + 0.3 * e_back(ph)
        text(img, 'h1a', 540, 340, WHITE, sc, a * e_out(ph * 1.5))
    p2 = prog(t, AN('system'), AN('system') + 0.4)
    if p2 > 0:
        text(img, 'h1b', 540, 480, ORANGE_L, 0.7 + 0.3 * e_back(p2), a * e_out(p2 * 1.5))


def scene_list(img, t):
    start = AN('pat') - 0.1
    logo_t = AN('medix1') - 0.1
    if t < start or t > logo_t + 1.5:
        return
    conv_a, conv_b = EN('manage_end') - 0.15, logo_t
    conv = e_io(prog(t, conv_a, conv_b))
    for i, (key, tag, col) in enumerate(CHIP_DEFS):
        ts = AN(tag) - 0.05
        p = e_out(prog(t, ts, ts + 0.45))
        if p <= 0:
            continue
        y = 450 + i * 150
        x = 540 + (1 - p) * 900
        x = x + (540 - x) * conv
        y = y + (780 - y) * conv
        s = 1.0 - 0.4 * conv
        al = 1.0 - prog(t, conv_b - 0.35, conv_b + 0.1)
        w, h = 700 * s, 118 * s
        rr(img, (x - w / 2, y - h / 2, x + w / 2, y + h / 2), 36 * s, (255, 255, 255), 0.15 * al)
        rr(img, (x - w / 2, y - h / 2, x - w / 2 + 20 * s, y + h / 2), 10 * s, col, al)
        circle(img, x - w / 2 + 70 * s, y, 20 * s, col, al)
        text(img, key, x - w / 2 + 120 * s, y, WHITE, 1.1 * s, al, 'l')
    am = prog(t, AN('manage') - 0.1, AN('manage') + 0.4) * (1 - prog(t, conv_b - 0.3, conv_b + 0.1))
    if am > 0:
        text(img, 'manage', 540, 290, ORANGE_L, 1.0, am)
    lp = prog(t, logo_t, logo_t + 0.7)
    if lp > 0:
        fade = 1 - prog(t, logo_t + 0.45, logo_t + 0.9)
        s = 0.6 + 0.4 * e_back(lp)
        glow(img, 540, 780, 520 * s, SKY, 0.5 * fade)
        logo(img, 'logo-primary-reversed-1600x420', 540, 780, 820 * s, fade)


def scene_features(img, t):
    f0 = AN('patient2') - 0.1
    mos0 = AN('ek') - 0.05
    mos_end = AN('ar1') - 0.15
    if t < f0 or t > mos_end + 0.6:
        return
    starts = [AN(tag) - 0.05 for tag, _, _ in FEAT_DEFS]
    ends = starts[1:] + [mos0]
    for (ts, (tag, name, lab)), te in zip(zip(starts, FEAT_DEFS), ends):
        p = e_out(prog(t, ts, ts + 0.5))
        a = p * (1 - prog(t, te, te + 0.3))
        if a <= 0.01:
            continue
        top = 350 + (1 - p) * 150
        screen_card(img, name, 540, top, 830, a)
        pill_text(img, lab, 540, 280, ORANGE, WHITE, 1.0, a, 50, 24)
    mp = prog(t, mos0, mos0 + 0.45)
    if mp > 0:
        fa = 1 - prog(t, mos_end, mos_end + 0.5)
        for i, name in enumerate(MOSAIC):
            col, row = i % 3, i // 3
            d = e_back(prog(t, mos0 + 0.05 + i * 0.1, mos0 + 0.5 + i * 0.1))
            cx = 540 + (col - 1) * 296
            top = 360 + row * 340
            w = 274 * (0.6 + 0.4 * d)
            screen_card(img, name, cx, top + (1 - d) * 40, w, e_out(prog(t, mos0 + 0.05 + i * 0.1, mos0 + 0.45 + i * 0.1)) * fa, 18)
        text(img, 'allin', 540, 262, WHITE, 1.0, e_out(mp) * fa)


def scene_offer(img, t):
    o0 = AN('ar1') - 0.1
    o_end = EN('n12k') + 1.2
    if t < o0 or t > AN('installation') + 0.2:
        return
    a = vis(t, o0, o_end, 0.3, 0.6)
    if a > 0:
        cx, cy = 540, 760
        spin = t * 0.25
        rays = Image.new('L', (W, H), 0)
        d = ImageDraw.Draw(rays)
        for i in range(14):
            ang = spin + i * (math.tau / 14)
            d.polygon([(cx, cy), (cx + 1700 * math.cos(ang), cy + 1700 * math.sin(ang)),
                       (cx + 1700 * math.cos(ang + 0.16), cy + 1700 * math.sin(ang + 0.16))], fill=255)
        rays = rays.point(lambda v: int(v * 0.16 * a))
        img.paste(ORANGE, (0, 0), rays)
        glow(img, cx, cy, 900, ORANGE, 0.55 * a)
    sp = AN('special')
    p1 = e_back(prog(t, sp - 0.05, sp + 0.45))
    p2 = e_back(prog(t, sp + 0.25, sp + 0.7))
    fa = 1 - prog(t, AN('kintu') - 0.3, AN('kintu') + 0.3)
    if p1 > 0:
        text(img, 'sp1', 540, 520, WHITE, 0.55 + 0.45 * p1, fa * clamp(p1 * 2))
    if p2 > 0:
        text(img, 'sp2', 540, 700, ORANGE_L, 0.55 + 0.45 * p2, fa * clamp(p2 * 2))
    spk = prog(t, sp - 0.05, sp + 1.2)
    if 0 < spk < 1:
        for i in range(10):
            ang = i * math.tau / 10
            r = 80 + 520 * e_out(spk)
            circle(img, 540 + r * math.cos(ang), 620 + r * math.sin(ang) * 0.8, 10 * (1 - spk) + 3, ORANGE_L, 1 - spk)
    rg = AN('regular')
    rp = e_out(prog(t, rg, rg + 0.4))
    shrink = e_io(prog(t, AN('kintu') - 0.2, AN('kintu') + 0.5))
    if rp > 0:
        y_reg = 1000 - shrink * 360
        s = 1.0 - 0.38 * shrink
        al = rp * (1 - 0.35 * shrink)
        text(img, 'regp', 540, y_reg - 120 * s, (200, 220, 250), 1.0 * s, al)
        np_ = e_out(prog(t, AN('n12k') - 0.15, AN('n12k') + 0.3))
        if np_ > 0:
            w, _ = number(img, '12,000', 540 - 40 * s, y_reg + 40 * s, WHITE, 0.62 * s, al * np_)
            text(img, 'bdt_s', 540 - 40 * s + w / 2 + 20 * s, y_reg + 60 * s, WHITE, 1.0 * s, al * np_, 'l')
            sl = e_io(prog(t, AN('n12k') + 0.25, EN('n12k') + 0.1))
            x0 = 540 - 40 * s - w / 2 - 20
            x1 = x0 + (w + 150 * s) * sl
            if sl > 0:
                ImageDraw.Draw(img).line([(x0, y_reg + 30 * s), (x1, y_reg + 30 * s)], fill=(255, 110, 40), width=int(14 * s))


def scene_price(img, t):
    k0 = AN('kintu')
    end = EN('branch') + 0.2
    if t < k0 - 0.1 or t > end + 0.6:
        return
    fa = 1 - prog(t, end, end + 0.5)
    onl = e_back(prog(t, k0 - 0.05, k0 + 0.4))
    if onl > 0:
        text(img, 'onlyn', 540, 830, ORANGE_L, 0.8 + 0.2 * onl, clamp(onl * 2) * fa)
    c0, c1 = k0 + 0.45, AN('n5k') + 0.15
    cd = prog(t, c0, c1)
    if cd > 0:
        val = 12000 - (12000 - 5000) * e_io(cd)
        s = f'{int(round(val)):,}'
        land = e_back(prog(t, c1, c1 + 0.5))
        sc = 1.0 + 0.12 * (1 - land) if t > c1 else 1.0
        glow(img, 540, 1060, 560, ORANGE, 0.45 * fa * clamp(cd * 2))
        w, h = number(img, s, 470, 1060, WHITE if cd < 1 else ORANGE_L, 0.92 * sc, fa)
        text(img, 'bdt', 470 + w / 2 + 20, 1110, WHITE, 0.85, fa * clamp(cd * 2), 'l')
    pp = e_out(prog(t, AN('per'), AN('per') + 0.4))
    if pp > 0:
        pill_text(img, 'per', 540, 1290, (255, 255, 255), NAVY, 1.0, pp * fa * 0.95, 40, 22)
    bp = e_back(prog(t, AN('branch') - 0.1, AN('branch') + 0.4))
    if bp > 0:
        s = 0.5 + 0.5 * bp
        circle(img, 905, 890, 110 * s, ORANGE, fa * clamp(bp * 2))
        text(img, 'off58', 905, 890, WHITE, 0.78 * s, fa * clamp(bp * 2))


def gift_icon(img, cx, cy, s, alpha):
    rr(img, (cx - 90 * s, cy - 10 * s, cx + 90 * s, cy + 110 * s), 14 * s, (255, 255, 255), alpha)
    rr(img, (cx - 100 * s, cy - 60 * s, cx + 100 * s, cy + 0 * s), 14 * s, (255, 255, 255), alpha)
    rr(img, (cx - 16 * s, cy - 60 * s, cx + 16 * s, cy + 110 * s), 4 * s, ORANGE, alpha)
    circle(img, cx - 36 * s, cy - 80 * s, 26 * s, ORANGE, alpha)
    circle(img, cx + 36 * s, cy - 80 * s, 26 * s, ORANGE, alpha)


def scene_install(img, t):
    t0, t1 = AN('installation') - 0.05, AN('ar2') - 0.1
    if t < t0 or t > t1 + 0.5:
        return
    a = vis(t, t0, t1, 0.3, 0.4)
    p = e_out(prog(t, t0, t0 + 0.5))
    y = 470 + (1 - p) * 300
    rr(img, (90, y, 990, y + 700), 56, (255, 255, 255), 0.14 * a)
    rr(img, (90, y, 110, y + 700), 10, ORANGE, a)
    text(img, 'inst', 540, y + 130, SKY, 1.0, a)
    text(img, 'inst_bn', 540, y + 240, WHITE, 1.0, a * e_out(prog(t, AN('installation') + 0.5, AN('installation') + 0.9)))
    n = e_back(prog(t, AN('n25k') - 0.05, AN('n25k') + 0.4))
    if n > 0:
        w25, _ = number(img, '25,000', 440, y + 420, WHITE, 0.92 * (0.8 + 0.2 * n), a * clamp(n * 2))
        text(img, 'bdt', 440 + w25 / 2 + 22, y + 460, ORANGE_L, 0.8, a * clamp(n * 2), 'l')
    ot = e_back(prog(t, AN('onetime') - 0.05, AN('onetime') + 0.4))
    if ot > 0:
        pill_text(img, 'onetime', 540, y + 600, ORANGE, WHITE, 0.8 + 0.2 * ot, a * clamp(ot * 2), 46, 20)


def scene_free(img, t):
    t0, t1 = AN('ar2') - 0.05, AN('yearly') - 0.15
    if t < t0 or t > t1 + 0.5:
        return
    a = vis(t, t0, t1, 0.3, 0.4)
    pulse = 1 + 0.035 * math.sin(t * 5)
    p = e_back(prog(t, t0 + 0.05, t0 + 0.7))
    glow(img, 540, 800, 720, GREEN, 0.5 * a)
    circle(img, 540, 800, 360 * pulse * (0.4 + 0.6 * p), GREEN, a * clamp(p * 2))
    circle(img, 540, 800, 330 * pulse * (0.4 + 0.6 * p), (255, 255, 255), 0.12 * a * clamp(p * 2))
    gift_icon(img, 540, 340, 0.85 * p, a)
    fp = a * e_out(prog(t, AN('prothom') - 0.05, AN('prothom') + 0.3))
    text(img, 'free1', 540, 640, WHITE, 1.0, fp)
    text(img, 'free_bn', 540, 1250, WHITE, 1.0, fp)
    fb = e_back(prog(t, AN('free1') - 0.1, AN('free1') + 0.3))
    if fb > 0:
        text(img, 'freeb', 540, 830, WHITE, (0.7 + 0.3 * fb) * pulse, a * clamp(fb * 2))
        text(img, 'afterinst', 540, 1010, (225, 255, 240), 1.0, a * clamp(fb * 2))
    for i in range(14):
        ang = i * math.tau / 14 + t
        r = 420 + 40 * math.sin(t * 3 + i)
        circle(img, 540 + r * math.cos(ang), 800 + r * math.sin(ang), 8, GREEN_L, 0.7 * a * clamp(fb * 2))


def scene_yearly(img, t):
    t0, t1 = AN('yearly') - 0.1, AN('dtm2') - 0.15
    if t < t0 or t > t1 + 0.5:
        return
    a = vis(t, t0, t1, 0.3, 0.4)
    text(img, 'yearly', 540, 390, SKY, 1.0, a)
    span = max(0.5, AN('paben') - t0 - 0.3)
    for i in range(12):
        col, row = i % 6, i // 6
        st = t0 + 0.15 + (i / 12.0) * span
        d = e_back(prog(t, st, st + 0.35))
        x = 100 + col * 150 + 70
        y = 520 + row * 160
        free = i >= 10
        gt = AN('free2') - 0.05 + (i - 10) * 0.12
        gr = prog(t, gt, gt + 0.25) if free else 0
        fill = (int(BLUE[0] + (GREEN[0] - BLUE[0]) * gr), int(BLUE[1] + (GREEN[1] - BLUE[1]) * gr), int(BLUE[2] + (GREEN[2] - BLUE[2]) * gr))
        s = 0.5 + 0.5 * d + (0.12 * math.sin(gr * math.pi) if free else 0)
        rr(img, (x - 66 * s, y - 62 * s, x + 66 * s, y + 62 * s), 24 * s, fill, a * clamp(d * 2))
        if free and gr > 0.5:
            text(img, 'free_s', x, y, WHITE, 0.95 * s, a)
        else:
            text(img, f'mo{i + 1}', x, y, WHITE, 0.9 * s, a * clamp(d * 2))
    mp = e_back(prog(t, AN('paben'), AN('paben') + 0.5))
    if mp > 0:
        pill_text(img, 'pay10', 540, 960, (255, 255, 255), NAVY, 0.9 + 0.1 * mp, a * clamp(mp * 2) * 0.95, 44, 24)
    fm = e_back(prog(t, AN('free2') - 0.05, AN('free2') + 0.4))
    if fm > 0:
        pill_text(img, 'm2free', 540, 1120, GREEN, WHITE, 0.85 + 0.15 * fm, a * clamp(fm * 2), 50, 28)


def scene_tagline(img, t):
    t0, t1 = AN('dtm2') - 0.05, AN('dtm3') - 0.1
    if t < t0 or t > t1 + 0.5:
        return
    a = vis(t, t0, t1, 0.4, 0.4)
    p = e_back(prog(t, t0, t0 + 0.7))
    glow(img, 540, 560, 640, SKY, 0.45 * a)
    logo(img, 'logo-primary-reversed-1600x420', 540, 560, 860 * (0.7 + 0.3 * p), a * clamp(p * 2))
    text(img, 'tag1', 540, 760, WHITE, 1.0, a * e_out(prog(t, AN('diag2') - 0.1, AN('diag2') + 0.3)))
    for i, (key, ts) in enumerate([('smart', AN('smart') - 0.25), ('fast', AN('fast') - 0.05), ('org', AN('org') - 0.1)]):
        q = e_back(prog(t, ts, ts + 0.45))
        if q <= 0:
            continue
        y = 905 + i * 112
        rr(img, (200, y - 50, 880, y + 50), 50, (255, 255, 255), 0.15 * a * clamp(q * 2))
        circle(img, 262, y, 30 * q, GREEN, a * clamp(q * 2))
        d = ImageDraw.Draw(img)
        if q > 0.6:
            d.line([(248, y), (258, y + 12), (278, y - 12)], fill=WHITE, width=7)
        text(img, key, 340, y, WHITE, 0.82, a * clamp(q * 2), 'l')
    bp = e_back(prog(t, AN('ajai1') - 0.05, AN('ajai1') + 0.5))
    if bp > 0:
        pulse = 1 + 0.03 * math.sin(t * 6)
        s = (0.6 + 0.4 * bp) * pulse
        rr(img, (540 - 400 * s, 1305 - 75 * s, 540 + 400 * s, 1305 + 75 * s), 75 * s, ORANGE, a * clamp(bp * 2))
        text(img, 'demo1', 540, 1305, WHITE, 0.95 * s, a * clamp(bp * 2))


def scene_product(img, t):
    t0, t1 = AN('dtm3') - 0.05, AN('call') - 0.2
    if t < t0 or t > t1 + 0.5:
        return
    a = vis(t, t0, t1, 0.35, 0.4)
    logo(img, 'logo-mark-512', 540, 470, 280, a * e_out(prog(t, t0, t0 + 0.5)))
    text(img, 'prod', 540, 700, (200, 220, 250), 1.0, a * e_out(prog(t, AN('product') - 0.05, AN('product') + 0.35)))
    text(img, 'dbd', 540, 840, WHITE, 1.0, a * e_out(prog(t, AN('product') + 0.2, AN('product') + 0.6)))
    text(img, 'tagline2', 540, 980, SKY, 1.0, a * e_out(prog(t, EN('ltd') - 0.2, EN('ltd') + 0.1)))
    circle(img, 540, 1100, 6, ORANGE_L, a)


def scene_contact(img, t):
    c0 = AN('call') - 0.15
    if t < c0:
        return
    CONTACTS = [(AN('call'), 'k_call', 'k_phone', BLUE), (AN('email'), 'k_mail', 'k_mailv', SKY), (AN('live'), 'k_demo', 'k_demov', GREEN)]
    cl0 = AN('ajai2')
    text(img, 'k_title', 540, 255, WHITE, 1.0, e_out(prog(t, c0, c0 + 0.5)) * (1 - prog(t, cl0 - 0.2, cl0 + 0.1)))
    for i, (ts, lk, vk, col) in enumerate(CONTACTS):
        p = e_out(prog(t, ts - 0.05, ts + 0.5))
        if p <= 0:
            continue
        y = 470 + i * 240
        x = 540 + (1 - p) * 700
        rr(img, (x - 460, y - 105, x + 460, y + 105), 44, (255, 255, 255), 0.14)
        rr(img, (x - 460, y - 105, x - 440, y + 105), 10, col)
        text(img, lk, x - 400, y - 55, col if col != BLUE else SKY, 1.0, 1.0, 'l')
        text(img, vk, x - 400, y + 28, WHITE, 1.0, 1.0, 'l')
    cp = e_back(prog(t, cl0 - 0.05, cl0 + 0.4))
    if cp > 0:
        text(img, 'cl1', 540, 255, ORANGE_L, 0.7 + 0.3 * cp, clamp(cp * 2))
    text(img, 'cl2', 540, 1150, WHITE, 1.0, e_out(prog(t, AN('diag3') - 0.1, AN('diag3') + 0.3)))
    text(img, 'cl3', 540, 1232, WHITE, 1.0, e_out(prog(t, AN('smart2') - 0.2, AN('smart2') + 0.2)))
    bp = e_back(prog(t, AN('manage2') - 0.1, AN('manage2') + 0.4))
    if bp > 0:
        pulse = 1 + 0.025 * math.sin(t * 6)
        s = (0.7 + 0.3 * bp) * pulse
        rr(img, (540 - 440 * s, 1390 - 85 * s, 540 + 440 * s, 1390 + 85 * s), 60 * s, ORANGE, clamp(bp * 2))
        text(img, 'btn1', 540, 1363, WHITE, 0.9 * s, clamp(bp * 2))
        text(img, 'btn2', 540, 1423, (255, 235, 220), 0.9 * s, clamp(bp * 2))


def subtitles(img, t):
    for i, (a, b, s) in enumerate(SUBS):
        if '@' in s or 'Call or' in s or 'Live Demo' in s or 'dt-medix' in s or '1575' in s:
            continue
        if a >= 60.0:
            continue
        if a - 0.05 <= t <= b:
            fa = clamp((t - a) / 0.15) * clamp((b - t) / 0.15 + 0.3)
            m = mask(f'sub{i}')
            w, h = m.width, m.height
            rr(img, (540 - w / 2 - 36, 1500 - h / 2 - 26, 540 + w / 2 + 36, 1500 + h / 2 + 26), 40, (0, 0, 0), 0.55 * fa)
            text(img, f'sub{i}', 540, 1500, WHITE, 1.0, fa)
            return


def frame(i):
    t = i / FPS
    img = background()
    glow(img, 220 + math.sin(t * 0.3) * 80, 380, 700, BLUE, 0.35)
    glow(img, 880 + math.cos(t * 0.25) * 90, 1450, 800, (30, 110, 220), 0.28)
    particles(img, t)
    if t < 60.3 or t > 64.8:
        pass
    logo(img, 'logo-primary-reversed-1600x420', 235, 120, 360, 0.95 * (1 - vis(t, AN('dtm2'), AN('dtm3') - 0.1, 0.3, 0.3)))
    scene_hook(img, t)
    scene_list(img, t)
    scene_features(img, t)
    scene_offer(img, t)
    scene_price(img, t)
    scene_install(img, t)
    scene_free(img, t)
    scene_yearly(img, t)
    scene_tagline(img, t)
    scene_product(img, t)
    scene_contact(img, t)
    fade = prog(t, DURATION - 0.4, DURATION)
    if fade > 0:
        img = Image.blend(img, Image.new('RGB', (W, H), (8, 16, 36)), fade)
    return img


def render_one(i):
    out = f'{WORK}/frames/f{i:05d}.jpg'
    if os.path.exists(out) and not os.environ.get('FORCE'):
        return
    frame(i).save(out, quality=94)


if __name__ == '__main__':
    mode = sys.argv[1]
    os.makedirs(WORK + '/frames', exist_ok=True)
    if mode == 'sheets':
        write_sheets()
    elif mode == 'extract':
        extract_masks()
    elif mode == 'render':
        total = int(DURATION * FPS)
        a = int(sys.argv[2]) if len(sys.argv) > 2 else 0
        b = int(sys.argv[3]) if len(sys.argv) > 3 else total
        with Pool(8) as p:
            for n, _ in enumerate(p.imap_unordered(render_one, range(a, b), chunksize=8)):
                if n % 200 == 0:
                    print('rendered', n, flush=True)
        print('done')
    elif mode == 'preview':
        os.makedirs(WORK + '/preview', exist_ok=True)
        for s in sys.argv[2:]:
            tt = float(s)
            frame(int(tt * FPS)).save(f'{WORK}/preview/t{tt:05.1f}.jpg', quality=90)
        print('ok')
