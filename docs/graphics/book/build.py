#!/usr/bin/env python3
"""Builds the DT Medix product book as HTML and PDF from one page spec.

Needs $TMPDIR/medix.json, the English marketing copy dumped from datatechbd.com resources/lang/en/medix.php.
"""
import html as H
import io
import json
import os
import shutil
import sys
import zlib

from PIL import Image, ImageFont

ROOT = '/Volumes/Mac Work/Projects/web-artisan/practices/dt-top-ten/DT-Medix-Diagnostic-Centers-Clinics'
OUT = f'{ROOT}/docs/graphics/book'
SHOTS = '/Volumes/Mac Work/Projects/web-artisan/practices/datatechbd.com/public/assets/images/products/dt-medix/screens'
LOGOS = f'{ROOT}/docs/graphics/logo/v1/png'
J = json.load(open(os.environ['TMPDIR'] + '/medix.json'))

BLUE = (11, 99, 206)
NAVY = (10, 20, 40)
SKY = (56, 168, 255)
SLATE = (74, 85, 104)
LIGHT = (234, 239, 247)
CLOUD = (245, 247, 250)
BORDER = (223, 230, 240)
GREEN = (22, 145, 90)
RED = (200, 60, 60)
WHITE = (255, 255, 255)

PW, PH = 595.28, 841.89
M = 42

CONTACT = {
    'email': 'sales@datatechbd.com',
    'phone': '+880 1575-888775',
    'whatsapp': '+880 1575-888775',
    'address': 'Flat 4-A, Dhumketu Shikder Tower, Holding No. 66, Paschim Monipur, Mirpur, Dhaka-1216, Bangladesh',
    'demo': 'https://dt-medix.datatechbd.com',
    'web': 'https://datatechbd.com/products/dt-medix',
}


def clean(s):
    s = (str(s).replace('’', "'").replace('‘', "'").replace('“', '"').replace('”', '"')
         .replace('–', '-').replace('—', '-').replace(' ', ' '))
    return s.encode('cp1252', 'replace').decode('cp1252')


# ---------------------------------------------------------------- fonts
def load_fonts():
    fonts = {}
    for i in range(8):
        try:
            f = ImageFont.truetype('/System/Library/Fonts/Helvetica.ttc', 1000, index=i)
        except Exception:
            break
        n = ' '.join(f.getname())
        if n.endswith('Regular') and 'reg' not in fonts:
            fonts['reg'] = f
        if n.endswith('Bold') and 'bold' not in fonts:
            fonts['bold'] = f
    assert 'reg' in fonts and 'bold' in fonts, fonts
    return fonts


FONTS = load_fonts()


def tw(s, size, bold=False):
    return FONTS['bold' if bold else 'reg'].getlength(clean(s)) * size / 1000.0


def wrap(s, size, width, bold=False):
    lines = []
    for para in clean(s).split('\n'):
        cur = ''
        for word in para.split(' '):
            trial = (cur + ' ' + word).strip()
            if tw(trial, size, bold) <= width or not cur:
                cur = trial
            else:
                lines.append(cur)
                cur = word
        lines.append(cur)
    return lines


# ---------------------------------------------------------------- PDF
class Pdf:
    def __init__(self):
        self.objs = []  # list of bytes (object bodies), id = index+1
        self.pages = []
        self.images = {}

    def add(self, body):
        self.objs.append(body)
        return len(self.objs)

    def image(self, path, key=None, bg=None):
        key = key or path
        if key in self.images:
            return self.images[key]
        im = Image.open(path)
        if im.mode == 'RGBA':
            base = Image.new('RGB', im.size, bg or WHITE)
            base.paste(im, mask=im.split()[3])
            im = base
        im = im.convert('RGB')
        buf = io.BytesIO()
        im.save(buf, 'JPEG', quality=88)
        data = buf.getvalue()
        name = f'Im{len(self.images) + 1}'
        oid = self.add(
            b'<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n' % (im.width, im.height, len(data)) + data + b'\nendstream')
        self.images[key] = (name, oid, im.size)
        return self.images[key]

    def save(self, path, info_title):
        f1 = self.add(b'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>')
        f2 = self.add(b'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>')
        xobj = ' '.join(f'/{n} {o} 0 R' for n, o, _ in self.images.values())
        page_ids = []
        pages_id_placeholder = len(self.objs) + 1 + 2 * len(self.pages) + 1
        for ops, annots in self.pages:
            raw = '\n'.join(ops).encode('latin-1')
            comp = zlib.compress(raw)
            cid = self.add(b'<< /Filter /FlateDecode /Length %d >>\nstream\n' % len(comp) + comp + b'\nendstream')
            ann = ''
            ids = []
            for rect, uri in annots:
                aid = self.add(('<< /Type /Annot /Subtype /Link /Rect [%.2f %.2f %.2f %.2f] /Border [0 0 0] /A << /S /URI /URI (%s) >> >>' % (*rect, uri)).encode('latin-1'))
                ids.append(aid)
            if ids:
                ann = '/Annots [' + ' '.join(f'{i} 0 R' for i in ids) + ']'
            pid = self.add(('<< /Type /Page /Parent PAGES 0 R /MediaBox [0 0 %.2f %.2f] /Contents %d 0 R /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> /XObject << %s >> >> %s >>' % (PW, PH, cid, f1, f2, xobj, ann)).encode('latin-1'))
            page_ids.append(pid)
        kids = ' '.join(f'{i} 0 R' for i in page_ids)
        pages_id = self.add(('<< /Type /Pages /Kids [%s] /Count %d >>' % (kids, len(page_ids))).encode('latin-1'))
        for pid in page_ids:
            self.objs[pid - 1] = self.objs[pid - 1].replace(b'PAGES 0 R', b'%d 0 R' % pages_id)
        cat = self.add(('<< /Type /Catalog /Pages %d 0 R >>' % pages_id).encode('latin-1'))
        info = self.add(('<< /Title (%s) /Author (Datatech BD Ltd.) /Producer (Datatech BD Ltd.) >>' % clean(info_title)).encode('latin-1'))
        out = io.BytesIO()
        out.write(b'%PDF-1.4\n%\xe2\xe3\xcf\xd3\n')
        offs = []
        for i, body in enumerate(self.objs):
            offs.append(out.tell())
            out.write(b'%d 0 obj\n' % (i + 1) + body + b'\nendobj\n')
        x = out.tell()
        out.write(b'xref\n0 %d\n0000000000 65535 f \n' % (len(self.objs) + 1))
        for o in offs:
            out.write(b'%010d 00000 n \n' % o)
        out.write(b'trailer\n<< /Size %d /Root %d 0 R /Info %d 0 R >>\nstartxref\n%d\n%%%%EOF\n' % (len(self.objs) + 1, cat, info, x))
        open(path, 'wb').write(out.getvalue())


def col(c):
    return '%.3f %.3f %.3f' % tuple(v / 255 for v in c)


class Canvas:
    def __init__(self, pdf):
        self.pdf = pdf
        self.ops = []
        self.annots = []
        self.maxy = 0

    def rect(self, x, y, w, h, fill=None, stroke=None, lw=0.6):
        if fill:
            self.ops.append(f'{col(fill)} rg {x:.2f} {PH - y - h:.2f} {w:.2f} {h:.2f} re f')
        if stroke:
            self.ops.append(f'{col(stroke)} RG {lw} w {x:.2f} {PH - y - h:.2f} {w:.2f} {h:.2f} re S')

    def rrect(self, x, y, w, h, r, fill=None, stroke=None, lw=0.6):
        y0 = PH - y - h
        k = 0.5523 * r
        p = (f'{x + r:.2f} {y0:.2f} m {x + w - r:.2f} {y0:.2f} l {x + w - r + k:.2f} {y0:.2f} {x + w:.2f} {y0 + r - k:.2f} {x + w:.2f} {y0 + r:.2f} c '
             f'{x + w:.2f} {y0 + h - r:.2f} l {x + w:.2f} {y0 + h - r + k:.2f} {x + w - r + k:.2f} {y0 + h:.2f} {x + w - r:.2f} {y0 + h:.2f} c '
             f'{x + r:.2f} {y0 + h:.2f} l {x + r - k:.2f} {y0 + h:.2f} {x:.2f} {y0 + h - r + k:.2f} {x:.2f} {y0 + h - r:.2f} c '
             f'{x:.2f} {y0 + r:.2f} l {x:.2f} {y0 + r - k:.2f} {x + r - k:.2f} {y0:.2f} {x + r:.2f} {y0:.2f} c h')
        if fill and stroke:
            self.ops.append(f'{col(fill)} rg {col(stroke)} RG {lw} w {p} B')
        elif fill:
            self.ops.append(f'{col(fill)} rg {p} f')
        elif stroke:
            self.ops.append(f'{col(stroke)} RG {lw} w {p} S')

    def line(self, x1, y1, x2, y2, c=BORDER, lw=0.6):
        self.ops.append(f'{col(c)} RG {lw} w {x1:.2f} {PH - y1:.2f} m {x2:.2f} {PH - y2:.2f} l S')

    def text(self, x, y, s, size=10, bold=False, c=NAVY, align='l'):
        s = clean(s)
        w = tw(s, size, bold)
        if align == 'c':
            x -= w / 2
        elif align == 'r':
            x -= w
        if size < 30 and y < PH - 45:
            self.maxy = max(self.maxy, y)
        esc = s.replace('\\', '\\\\').replace('(', '\\(').replace(')', '\\)')
        self.ops.append(f'BT {col(c)} rg /{"F2" if bold else "F1"} {size} Tf {x:.2f} {PH - y:.2f} Td ({esc}) Tj ET')
        return w

    def image(self, path, x, y, w, h=None, key=None, bg=None):
        name, _, (iw, ih) = self.pdf.image(path, key, bg)
        h = h if h is not None else w * ih / iw
        self.ops.append(f'q {w:.2f} 0 0 {h:.2f} {x:.2f} {PH - y - h:.2f} cm /{name} Do Q')
        return h

    def link(self, x, y, w, h, uri):
        self.annots.append(((x, PH - y - h, x + w, PH - y), uri))

    def check(self, cx, cy, ok):
        if ok:
            self.ops.append(f'{col(GREEN)} rg {cx - 6:.2f} {PH - cy - 6:.2f} 12 12 re f')
            self.ops.append(f'1 1 1 RG 1.6 w {cx - 3.2:.2f} {PH - cy + 0.2:.2f} m {cx - 0.8:.2f} {PH - cy - 2.6:.2f} l {cx + 3.4:.2f} {PH - cy + 3:.2f} l S')
        else:
            self.ops.append(f'{col(RED)} RG 1.4 w {cx - 3:.2f} {PH - cy - 3:.2f} m {cx + 3:.2f} {PH - cy + 3:.2f} l S {cx - 3:.2f} {PH - cy + 3:.2f} m {cx + 3:.2f} {PH - cy - 3:.2f} l S')

    def done(self):
        print('page', len(self.pdf.pages) + 1, 'content bottom %.0f of %.0f' % (self.maxy, PH - 45))
        self.pdf.pages.append((self.ops, self.annots))


# ---------------------------------------------------------------- content
def shot(name):
    return f'{SHOTS}/{name}.jpg'


MODS = J['modules']['items']
PROBLEMS = J['problems']['items']
FAQ = J['faq']['items']

FLOW = [
    ('Register', 'Create or find the patient; duplicate warning on mobile and name.'),
    ('Book', 'Pick a doctor slot and issue a queue token, or skip for walk-ins.'),
    ('Order', 'Add tests and packages at the reception counter, apply approved discounts.'),
    ('Pay', 'Take cash, partial payment or leave a due; print invoice and receipt.'),
    ('Collect', 'Print barcode labels, collect the sample and record the collector.'),
    ('Receive', 'Lab scans the label, receives or rejects the sample with a reason.'),
    ('Result', 'Enter results; flags and critical alerts are calculated for you.'),
    ('Report', 'Use a table or narrative template; view the finished result.'),
]

ROLES = [
    ('Owner', 'Everything, across all branches: settings, staff, branches, reports, payroll.'),
    ('Manager', 'Runs a branch: approvals, staff, catalog, attendance, leave.'),
    ('Reception', 'Patients, appointments, test orders, payments and printing.'),
    ('Sample collector', 'Collects samples at the counter or on a home visit.'),
    ('Lab technician', 'Receives samples, works the worklist, enters results.'),
    ('Pathologist', 'Reviews results, handles critical values and report templates.'),
    ('Doctor', 'Own schedule, queue and patient visit list.'),
    ('Accountant', 'Payments, refunds, dues and payroll.'),
]

ANSWERS = [
    ('Sample mix-ups', 'Barcode labels printed at collection; scan to open any sample.'),
    ('Duplicate patients', 'Warning on mobile and similar name; family members linked.'),
    ('Late and lost reports', 'Worklist with turnaround countdown and urgent pinning.'),
    ('Dues and discounts', 'Approval limits with reasons; due balance is always calculated.'),
    ('Critical values', 'Acknowledgement with name and time before the result moves on.'),
    ('Staff time and salary', 'Device punches and leave feed the monthly payroll sheet.'),
]
READY = ['Patients and family linking', 'Appointments and tokens', 'Reception billing', 'Test catalog and packages', 'Barcode samples', 'Lab worklist', 'Result entry and flags', 'Critical value log', 'Report templates', 'Approvals and refunds', 'Branches and roles', 'Attendance, leave, payroll']

BOOK = [
    {'t': 'cover'},
    {'t': 'about'},
    {'t': 'overview'},
    {'t': 'problems'},
    {'t': 'benefits'},
    {'t': 'flow'},
]
for i in range(0, len(MODS), 2):
    BOOK.append({'t': 'modules', 'items': list(range(i, min(i + 2, len(MODS))))})
BOOK += [
    {'t': 'roles'},
    {'t': 'gallery'},
    {'t': 'security'},
    {'t': 'integrations'},
    {'t': 'roadmap'},
    {'t': 'compare'},
    {'t': 'steps'},
    {'t': 'faq', 'items': list(range(0, 7))},
    {'t': 'faq', 'items': list(range(7, len(FAQ)))},
    {'t': 'contact'},
]

TITLES = {
    'about': 'About DT Medix and Datatech BD Ltd.',
    'overview': 'Overview',
    'problems': 'Problems DT Medix solves',
    'benefits': 'Benefits for your center',
    'flow': 'How one patient visit flows',
    'roles': 'Staff roles and who does what',
    'gallery': 'Screens at a glance',
    'security': 'Security and privacy',
    'integrations': 'Devices, printers and channels',
    'roadmap': 'Roadmap',
    'compare': 'Compared with paper and spreadsheets',
    'steps': 'Getting started and pricing',
    'faq': 'Frequently asked questions',
    'contact': 'Contact',
}


def page_title(i, p):
    if p['t'] == 'modules':
        return 'Features: ' + MODS[p['items'][0]]['title'].split(' and ')[0]
    return TITLES.get(p['t'], '')


# ---------------------------------------------------------------- PDF layout
class Flow:
    def __init__(self, c, y=100):
        self.c = c
        self.y = y

    def h2(self, kicker, title, w=PW - 2 * M, x=M):
        c = self.c
        c.text(x, self.y, clean(kicker).upper(), 8.5, True, BLUE)
        self.y += 20
        for ln in wrap(title, 19, w, True):
            c.text(x, self.y, ln, 19, True, NAVY)
            self.y += 23
        self.y += 4

    def para(self, s, x=M, w=PW - 2 * M, size=9.8, c=SLATE, lead=None, bold=False):
        lead = lead or size * 1.5
        for ln in wrap(s, size, w, bold):
            self.c.text(x, self.y, ln, size, bold, c)
            self.y += lead
        self.y += 3

    def bullets(self, items, x=M, w=PW - 2 * M, size=9.4, gap=3.5, c=SLATE):
        for it in items:
            lines = wrap(it, size, w - 14)
            self.c.rect(x + 1, self.y - 6.2, 4.2, 4.2, BLUE)
            for ln in lines:
                self.c.text(x + 12, self.y, ln, size, False, c)
                self.y += size * 1.45
            self.y += gap

    def card(self, x, y, w, title, desc, h=None, icon_color=BLUE):
        c = self.c
        tl = wrap(title, 10.6, w - 28, True)
        dl = wrap(desc, 8.8, w - 28)
        need = 14 + len(tl) * 13 + 4 + len(dl) * 12.6 + 12
        h = h or need
        c.rrect(x, y, w, h, 7, CLOUD, BORDER)
        c.rrect(x + 12, y + 13, 5, 5, 1.5, icon_color)
        ty = y + 22
        for ln in tl:
            c.text(x + 22, ty, ln, 10.6, True, NAVY)
            ty += 13
        ty += 3
        for ln in dl:
            c.text(x + 14, ty, ln, 8.8, False, SLATE)
            ty += 12.6
        return h

    def cards(self, items, cols=2, gap=12, x=M, w=PW - 2 * M):
        cw = (w - gap * (cols - 1)) / cols
        for r in range(0, len(items), cols):
            row = items[r:r + cols]
            hs = []
            for t, d in row:
                tl = wrap(t, 10.6, cw - 28, True)
                dl = wrap(d, 8.8, cw - 28)
                hs.append(14 + len(tl) * 13 + 4 + len(dl) * 12.6 + 12)
            h = max(hs)
            for k, (t, d) in enumerate(row):
                self.card(x + k * (cw + gap), self.y, cw, t, d, h)
            self.y += h + gap


def chrome(c, no, title):
    c.rect(0, 0, PW, 6, BLUE)
    c.text(M, 30, 'DT Medix', 9, True, BLUE)
    c.text(M + 45, 30, ' |  Product book', 9, False, SLATE)
    c.text(PW - M, 30, title, 9, False, SLATE, 'r')
    c.line(M, 40, PW - M, 40, BORDER)
    c.line(M, PH - 36, PW - M, PH - 36, BORDER)
    c.text(M, PH - 20, 'Datatech BD Ltd.  |  datatechbd.com  |  ' + CONTACT['phone'], 8, False, SLATE)
    c.text(PW - M, PH - 20, str(no), 8.5, True, BLUE, 'r')


def pdf_cover(pdf):
    c = Canvas(pdf)
    c.rect(0, 0, PW, PH, NAVY)
    c.rect(0, 0, PW, 430, BLUE)
    c.rect(0, 430, PW, 5, SKY)
    c.image(f'{LOGOS}/logo-primary-reversed-1600x420.png', M, 56, 250, key='logo-rev', bg=BLUE)
    c.text(M, 200, 'PRODUCT BOOK', 10, True, (190, 220, 255))
    y = 232
    for ln in ['Diagnostic Center,', 'Pathology Lab & Clinic', 'Management Software']:
        c.text(M, y, ln, 33, True, WHITE)
        y += 40
    for ln in wrap('One cloud system for patients, appointments, billing, barcode samples, lab results, staff attendance and payroll. Built for Bangladesh, in Bangla and English.', 12, 440):
        c.text(M, y + 10, ln, 12, False, (225, 238, 255))
        y += 17
    c.image(shot('reception'), 330, 458, 210, key='reception')
    c.image(shot('worklist'), 70, 472, 196, key='worklist')
    c.rrect(M, 600 + 130, 250, 50, 8, None, SKY, 1)
    c.text(M + 16, 600 + 152, 'Free live demo', 9, True, SKY)
    c.text(M + 16, 600 + 168, 'dt-medix.datatechbd.com', 11, True, WHITE)
    c.link(M, 730, 250, 50, CONTACT['demo'])
    c.text(PW - M, PH - 50, 'A product of Datatech BD Ltd.', 10, True, WHITE, 'r')
    c.text(PW - M, PH - 34, 'Software that runs business.', 9, False, (190, 210, 235), 'r')
    c.done()


def pdf_about(pdf, no, toc):
    c = Canvas(pdf)
    chrome(c, no, TITLES['about'])
    f = Flow(c)
    f.h2('Introduction', 'What DT Medix is, and who builds it')
    f.para('DT Medix is diagnostic center, pathology lab and clinic management software. It follows a patient through the real flow of a lab, from registration and appointment to the invoice, the barcode sample, the lab result and the report, and it also runs the staff side with attendance, leave and payroll.')
    f.para('DT Medix is built by Datatech BD Ltd., a Dhaka software company. Each center receives its own installation and database, so patient data is never mixed with another business. The whole interface works in English and Bangla.')
    f.y += 8
    f.h2('Contents', 'What is inside this book')
    for label, pg in toc:
        c.text(M, f.y, label, 10, False, NAVY)
        c.text(PW - M, f.y, str(pg), 10, True, BLUE, 'r')
        lw = tw(label, 10)
        dots = int((PW - 2 * M - lw - 30) / 4)
        c.text(M + lw + 6, f.y, '.' * dots, 10, False, BORDER)
        f.y += 27
    c.done()


def pdf_overview(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['overview'])
    f = Flow(c)
    f.h2(J['overview']['kicker'], J['overview']['title'])
    for p in J['overview']['body']:
        f.para(p)
    f.y += 6
    box_top = f.y
    c.rrect(M, box_top, PW - 2 * M, 300, 9, LIGHT)
    f.y += 26
    c.text(M + 18, f.y, J['overview']['glance_title'], 12, True, NAVY)
    f.y += 20
    f.bullets(J['overview']['glance'], M + 18, PW - 2 * M - 36, 9.6, 5)
    # facts strip
    y = box_top + 318
    facts = J['facts']
    fw = (PW - 2 * M - 10 * 2) / 3
    for i, ft in enumerate(facts):
        x = M + (i % 3) * (fw + 10)
        yy = y + (i // 3) * 58
        c.rrect(x, yy, fw, 50, 7, WHITE, BORDER)
        c.text(x + 12, yy + 20, ft['label'].upper(), 7.5, True, BLUE)
        c.text(x + 12, yy + 38, ft['value'], 10.2, True, NAVY)
    c.done()


def pdf_problems(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['problems'])
    f = Flow(c)
    f.h2(J['problems']['kicker'], J['problems']['title'])
    f.para(J['problems']['description'])
    f.y += 4
    f.cards([(i['title'], i['desc']) for i in PROBLEMS], 2)
    f.y += 6
    f.h2('How we answer', 'Each problem, and the DT Medix answer')
    top = f.y
    rh = 30
    c.rect(M, top, PW - 2 * M, 24, BLUE)
    c.text(M + 12, top + 16, 'PROBLEM', 8.5, True, WHITE)
    c.text(M + 170, top + 16, 'ANSWER', 8.5, True, WHITE)
    for i, (a, b) in enumerate(ANSWERS):
        y = top + 24 + i * rh
        c.rect(M, y, PW - 2 * M, rh, CLOUD if i % 2 == 0 else WHITE)
        c.text(M + 12, y + 19, a, 9.6, True, NAVY)
        c.text(M + 170, y + 19, b, 9.2, False, SLATE)
    c.rect(M, top, PW - 2 * M, 24 + len(ANSWERS) * rh, None, BORDER)
    c.done()


def pdf_benefits(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['benefits'])
    f = Flow(c)
    f.h2(J['benefits']['kicker'], J['benefits']['title'])
    f.para(J['benefits']['description'])
    f.y += 4
    cw = (PW - 2 * M - 24) / 3
    top = f.y
    heights = []
    for col_ in J['benefits']['columns']:
        h = 44
        for p in col_['points']:
            h += len(wrap(p, 9, cw - 36)) * 12.8 + 5
        heights.append(h + 10)
    H_ = max(heights)
    for k, col_ in enumerate(J['benefits']['columns']):
        x = M + k * (cw + 12)
        c.rrect(x, top, cw, H_, 8, CLOUD, BORDER)
        c.rect(x, top + 10, 4, 24, BLUE)
        c.text(x + 16, top + 27, col_['title'], 12, True, NAVY)
        y = top + 54
        for p in col_['points']:
            c.rect(x + 16, y - 6, 4, 4, BLUE)
            for ln in wrap(p, 9, cw - 36):
                c.text(x + 26, y, ln, 9, False, SLATE)
                y += 12.8
            y += 5
    f.y = top + H_ + 26
    f.h2(J['audience']['kicker'], J['audience']['title'])
    f.cards([(i['title'], i['desc']) for i in J['audience']['items']], 2, 10)
    c.done()


def pdf_flow(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['flow'])
    f = Flow(c)
    f.h2('Workflow', 'From the front desk to the finished report')
    f.para('Every step uses the same patient, order and sample records, so nothing is typed twice and every handover is visible. The numbers below are the screens a patient passes through in a normal diagnostic center visit.')
    f.y += 10
    cw = (PW - 2 * M - 12) / 2
    top = f.y
    rows = (len(FLOW) + 1) // 2
    for i, (t, d) in enumerate(FLOW):
        x = M + (i % 2) * (cw + 12)
        y = top + (i // 2) * 112
        c.rrect(x, y, cw, 100, 9, CLOUD, BORDER)
        c.rrect(x + 14, y + 14, 30, 30, 15, BLUE)
        c.text(x + 29, y + 34.5, str(i + 1), 13, True, WHITE, 'c')
        c.text(x + 56, y + 34, t, 13, True, NAVY)
        yy = y + 62
        for ln in wrap(d, 9.2, cw - 28):
            c.text(x + 14, yy, ln, 9.2, False, SLATE)
            yy += 12.8
    f.y = top + rows * 112 + 14
    f.h2('Behind the counter', 'The business side runs on the same data')
    f.bullets([
        'Discounts, refunds and cancellations after collection wait for a manager approval, with a reason on record.',
        'Staff punch in at a ZKTeco fingerprint device, or a manager marks attendance by hand.',
        'Late minutes, overtime, leave and holidays feed the monthly payroll and printable payslips.',
        'Owners switch between branches; branch staff only ever see their own branch.',
    ])
    c.done()


def pdf_modules(pdf, no, p):
    c = Canvas(pdf)
    chrome(c, no, page_title(0, p))
    y = 58
    for k, idx in enumerate(p['items']):
        m = MODS[idx]
        top = y + k * 372
        c.rrect(M, top, PW - 2 * M, 358, 10, WHITE, BORDER)
        tx = M + 18
        tw_ = 262
        c.text(tx, top + 26, 'FEATURE %02d' % (idx + 1), 8, True, BLUE)
        yy = top + 46
        for ln in wrap(m['title'], 15, tw_, True):
            c.text(tx, yy, ln, 15, True, NAVY)
            yy += 19
        yy += 3
        for ln in wrap(m['desc'], 9.2, tw_):
            c.text(tx, yy, ln, 9.2, False, SLATE)
            yy += 13
        yy += 6
        for pt in m['points']:
            lines = wrap(pt, 8.9, tw_ - 14)
            c.rect(tx + 1, yy - 6, 4, 4, BLUE)
            for ln in lines:
                c.text(tx + 12, yy, ln, 8.9, False, NAVY)
                yy += 12.4
            yy += 3.2
        if yy > top + 350:
            print('WARN overflow module', idx, yy - top, file=sys.stderr)
        # screenshot
        iw = 218
        ih = c.image(shot(m['img']), PW - M - 18 - iw, top + 16, iw, key=m['img'])
        c.rect(PW - M - 18 - iw, top + 16, iw, ih, None, BORDER, 0.8)
        cap = wrap(m['alt'], 7.6, iw)
        cy = top + 16 + ih + 11
        for ln in cap[:2]:
            c.text(PW - M - 18 - iw, cy, ln, 7.6, False, SLATE)
            cy += 9.5
    c.done()


def pdf_roles(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['roles'])
    f = Flow(c)
    f.h2('Access control', 'Eight roles, each with its own menu and actions')
    f.para('Menus, buttons and actions follow permissions. Branch staff only see their own branch, and every record view and override is logged. Owners can change roles and permissions for their center.')
    f.y += 6
    top = f.y
    rh = 52
    c.rect(M, top, PW - 2 * M, 26, BLUE)
    c.text(M + 14, top + 17, 'ROLE', 8.5, True, WHITE)
    c.text(M + 150, top + 17, 'WHAT THEY DO', 8.5, True, WHITE)
    for i, (r, d) in enumerate(ROLES):
        y = top + 26 + i * rh
        c.rect(M, y, PW - 2 * M, rh, CLOUD if i % 2 == 0 else WHITE, None)
        c.text(M + 14, y + 22, r, 10.5, True, NAVY)
        yy = y + 21
        for ln in wrap(d, 9.4, PW - 2 * M - 164):
            c.text(M + 150, yy, ln, 9.4, False, SLATE)
            yy += 13
    c.rect(M, top, PW - 2 * M, 26 + len(ROLES) * rh, None, BORDER)
    f.y = top + 26 + len(ROLES) * rh + 26
    f.h2('Setup', 'Branches and data separation')
    f.bullets([
        'One installation can hold many branches, each with its own staff, orders and samples.',
        'Optional branch price lists let each branch charge its own test prices.',
        'Cross-branch access is blocked and covered by automated tests.',
        'Optional two-step verification for staff sign-in.',
    ])
    c.done()


def pdf_gallery(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['gallery'])
    f = Flow(c)
    f.h2(J['gallery']['kicker'], 'More screens from the live demo')
    f.para('Real screens with sample data. Open the free demo at dt-medix.datatechbd.com to click through every one.')
    f.y += 6
    names = [('patients', 'Patient list'), ('orders', 'Test orders and invoices'), ('packages', 'Health packages'),
             ('doctors', 'Doctors and fees'), ('doctor-detail', 'Doctor weekly schedule'), ('sample-receive', 'Receive samples'),
             ('refunds', 'Refund requests'), ('leave', 'Leave requests'), ('hr-setup', 'Shifts and ZKTeco devices')]
    cw = (PW - 2 * M - 24) / 3
    ih_max = 0
    top = f.y
    for i, (n, cap) in enumerate(names):
        x = M + (i % 3) * (cw + 12)
        row = i // 3
        y = top + row * 214
        c.rect(x - 1, y - 1, cw + 2, 168, None, BORDER)
        # crop to a fixed window so tiles line up
        c.ops.append(f'q {x:.2f} {PH - y - 166:.2f} {cw:.2f} 166 re W n')
        name, _, (iw, ih) = pdf.image(shot(n), key=n)
        h = cw * ih / iw
        c.ops.append(f'q {cw:.2f} 0 0 {h:.2f} {x:.2f} {PH - y - h:.2f} cm /{name} Do Q')
        c.ops.append('Q')
        c.text(x, y + 184, cap, 9.2, True, NAVY)
    c.done()


def pdf_cardpage(pdf, no, key, kicker, title, desc, items, extra=None):
    c = Canvas(pdf)
    chrome(c, no, TITLES[key])
    f = Flow(c)
    f.h2(kicker, title)
    if desc:
        f.para(desc)
    f.y += 4
    f.cards([(i['title'], i['desc']) for i in items], 2)
    return c, f


def pdf_security_integrations(pdf, no, which):
    if which == 'security':
        c, f = pdf_cardpage(pdf, no, 'security', J['security']['kicker'], J['security']['title'], None, J['security']['items'])
        f.y += 8
        f.h2('Data protection', 'Patient data is handled with care')
        iy = f.y
        ih = c.image(shot('access-log'), PW - M - 200, iy - 40, 200, key='access-log')
        c.rect(PW - M - 200, iy - 40, 200, ih, None, BORDER, 0.8)
        c.text(PW - M - 200, iy - 40 + ih + 11, 'Patient access log with user, time and action', 7.8, False, SLATE)
        f.bullets([
            'Message templates never contain names, results or diagnoses.',
            'Released reports are never edited in place; a correction is a new version.',
            'Every patient record view is stored with user, time and address.',
            'Idle sessions sign out automatically.',
        ], w=PW - 2 * M - 224)
    else:
        c, f = pdf_cardpage(pdf, no, 'integrations', J['integrations']['kicker'], J['integrations']['title'], J['integrations']['description'], J['integrations']['items'])
        f.y += 8
        f.h2('Hardware', 'What you need at the counter')
        iy = f.y
        ih = c.image(shot('invoice-print'), PW - M - 200, iy - 40, 200, key='invoice-print')
        c.rect(PW - M - 200, iy - 40, 200, ih, None, BORDER, 0.8)
        c.text(PW - M - 200, iy - 40 + ih + 11, 'Printed invoice and money receipt', 7.8, False, SLATE)
        f.bullets([
            'A computer, tablet or phone with a modern web browser.',
            'A thermal (80 mm) or A4 printer for invoices and receipts.',
            'A label printer for sample barcodes (optional).',
            'A ZKTeco attendance device with push support (optional).',
        ], w=PW - 2 * M - 224)
    c.done()


def pdf_roadmap(pdf, no):
    c, f = pdf_cardpage(pdf, no, 'roadmap', J['roadmap']['kicker'], J['roadmap']['title'], J['roadmap']['description'], J['roadmap']['items'])
    f.y += 4
    f.para(J['roadmap']['note'], size=9.2, c=SLATE)
    f.y += 8
    top = f.y
    bw = (PW - 2 * M - 14) / 2
    c.rrect(M, top, bw, 200, 9, LIGHT)
    c.text(M + 16, top + 26, 'READY TODAY', 9, True, GREEN)
    yy = top + 48
    for it in READY:
        c.check(M + 22, yy - 3, True)
        c.text(M + 36, yy, it, 9.2, False, NAVY)
        yy += 14.5
    c.rrect(M + bw + 14, top, bw, 200, 9, BLUE)
    c.text(M + bw + 30, top + 26, 'COMING NEXT', 9, True, WHITE)
    yy = top + 50
    for it in J['roadmap']['items']:
        c.rect(M + bw + 30, yy - 7, 5, 5, SKY)
        c.text(M + bw + 44, yy, it['title'], 9.6, False, WHITE)
        yy += 24
    c.done()


def pdf_compare(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['compare'])
    f = Flow(c)
    f.h2(J['compare']['kicker'], J['compare']['title'])
    f.para(J['compare']['description'])
    f.y += 6
    top = f.y
    lw = 230
    cw = (PW - 2 * M - lw) / 3
    c.rect(M, top, PW - 2 * M, 30, NAVY)
    for i, h in enumerate(J['compare']['cols']):
        x = M + lw + i * cw
        if i == 2:
            c.rect(x, top, cw, 30, BLUE)
        c.text(x + cw / 2, top + 19, h, 9.5, True, WHITE, 'c')
    rh = 46
    for r, (label, vals) in enumerate(J['compare']['rows']):
        y = top + 30 + r * rh
        c.rect(M, y, PW - 2 * M, rh, CLOUD if r % 2 == 0 else WHITE)
        for k, ln in enumerate(wrap(label, 9.4, lw - 20)[:2]):
            c.text(M + 12, y + 15 + k * 11.5 - (5 if len(wrap(label, 9.4, lw - 20)) > 1 else 0), ln, 9.4, False, NAVY)
        for i, v in enumerate(vals):
            c.check(M + lw + i * cw + cw / 2, y + rh / 2, v)
    c.rect(M, top, PW - 2 * M, 30 + len(J['compare']['rows']) * rh, None, BORDER)
    c.done()


def pdf_steps(pdf, no):
    c = Canvas(pdf)
    chrome(c, no, TITLES['steps'])
    f = Flow(c)
    f.h2(J['steps']['kicker'], J['steps']['title'])
    top = f.y + 4
    for i, s in enumerate(J['steps']['items']):
        y = top + i * 52
        c.rrect(M, y, 34, 34, 17, BLUE)
        c.text(M + 17, y + 22, str(i + 1), 13, True, WHITE, 'c')
        c.text(M + 50, y + 14, s['title'], 11, True, NAVY)
        yy = y + 28
        for ln in wrap(s['desc'], 9.2, PW - 2 * M - 52):
            c.text(M + 50, yy, ln, 9.2, False, SLATE)
            yy += 12.4
        if i < len(J['steps']['items']) - 1:
            c.line(M + 17, y + 36, M + 17, y + 52, BORDER, 1.2)
    f.y = top + 5 * 52 + 14
    f.h2(J['pricing']['kicker'], J['pricing']['title'])
    f.para(J['pricing']['description'])
    top = f.y + 2
    c.rrect(M, top, PW - 2 * M, 150, 10, BLUE)
    c.text(M + 20, top + 30, J['pricing']['price'], 20, True, WHITE)
    c.text(M + 20, top + 48, J['pricing']['unit'] + '. ' + J['pricing']['regular'] + '.', 8.8, False, (215, 232, 255))
    yy = top + 72
    half = (PW - 2 * M - 40) / 2
    for i, line in enumerate(J['pricing']['included']):
        x = M + 20 + (i % 2) * half
        yv = yy + (i // 2) * 22
        c.rect(x, yv - 6, 4, 4, SKY)
        c.text(x + 12, yv, line, 9, False, WHITE)
    c.text(M + 20, top + 140, 'Free demo first. Sales: ' + CONTACT['phone'] + '  |  ' + CONTACT['email'], 9, True, WHITE)
    c.done()


def pdf_faq(pdf, no, p):
    c = Canvas(pdf)
    chrome(c, no, TITLES['faq'])
    f = Flow(c)
    f.h2('FAQ' if p['items'][0] == 0 else 'FAQ (continued)', 'Questions owners ask us')
    for i in p['items']:
        q = FAQ[i]
        for ln in wrap(q['q'], 11.4, PW - 2 * M, True):
            c.text(M, f.y, ln, 11.4, True, NAVY)
            f.y += 16
        for ln in wrap(q['a'], 10.2, PW - 2 * M):
            c.text(M, f.y, ln, 10.2, False, SLATE)
            f.y += 15
        f.y += 14
    if f.y > PH - 50:
        print('WARN overflow faq', f.y, file=sys.stderr)
    c.done()


def pdf_contact(pdf, no):
    c = Canvas(pdf)
    c.rect(0, 0, PW, PH, NAVY)
    c.rect(0, 0, PW, 300, BLUE)
    c.image(f'{LOGOS}/logo-primary-reversed-1600x420.png', M, 56, 230, key='logo-rev', bg=BLUE)
    c.text(M, 170, 'See it with your own tests,', 26, True, WHITE)
    c.text(M, 202, 'doctors and branches.', 26, True, WHITE)
    c.text(M, 236, 'Open the free live demo, then talk to our sales team.', 12, False, (225, 238, 255))
    rows = [('Free live demo', 'dt-medix.datatechbd.com', CONTACT['demo']),
            ('Email', CONTACT['email'], 'mailto:' + CONTACT['email']),
            ('Phone', CONTACT['phone'], 'tel:+8801575888775'),
            ('WhatsApp', CONTACT['whatsapp'], 'https://wa.me/8801575888775'),
            ('Website', 'datatechbd.com/products/dt-medix', CONTACT['web'])]
    y = 350
    for label, value, uri in rows:
        c.text(M, y, label.upper(), 8.5, True, SKY)
        c.text(M, y + 20, value, 14, True, WHITE)
        c.link(M, y + 4, 330, 24, uri)
        y += 56
    c.text(M, y + 4, 'ADDRESS', 8.5, True, SKY)
    yy = y + 24
    for ln in wrap(CONTACT['address'], 12, 330):
        c.text(M, yy, ln, 12, False, WHITE)
        yy += 17
    c.text(M, PH - 56, 'Datatech BD Ltd.', 12, True, WHITE)
    c.text(M, PH - 40, 'Software that runs business.', 10, False, (190, 210, 235))
    c.done()


def toc_entries():
    entries = []
    for i, p in enumerate(BOOK):
        no = i + 1
        t = p['t']
        if t == 'overview':
            entries.append(('Overview and at a glance', no))
        elif t == 'problems':
            entries.append(('Problems DT Medix solves', no))
        elif t == 'benefits':
            entries.append(('Benefits and who it is for', no))
        elif t == 'flow':
            entries.append(('How one patient visit flows', no))
        elif t == 'modules' and p['items'][0] == 0:
            entries.append(('Features in detail (14 modules)', no))
        elif t == 'roles':
            entries.append(('Staff roles and branches', no))
        elif t == 'gallery':
            entries.append(('Screens at a glance', no))
        elif t == 'security':
            entries.append(('Security and privacy', no))
        elif t == 'integrations':
            entries.append(('Devices, printers and channels', no))
        elif t == 'roadmap':
            entries.append(('Roadmap', no))
        elif t == 'compare':
            entries.append(('Compared with paper and spreadsheets', no))
        elif t == 'steps':
            entries.append(('Getting started and pricing', no))
        elif t == 'faq' and p['items'][0] == 0:
            entries.append(('Frequently asked questions', no))
        elif t == 'contact':
            entries.append(('Contact', no))
    return entries


def build_pdf(path):
    pdf = Pdf()
    toc = toc_entries()
    for i, p in enumerate(BOOK):
        no = i + 1
        t = p['t']
        if t == 'cover':
            pdf_cover(pdf)
        elif t == 'about':
            pdf_about(pdf, no, toc)
        elif t == 'overview':
            pdf_overview(pdf, no)
        elif t == 'problems':
            pdf_problems(pdf, no)
        elif t == 'benefits':
            pdf_benefits(pdf, no)
        elif t == 'flow':
            pdf_flow(pdf, no)
        elif t == 'modules':
            pdf_modules(pdf, no, p)
        elif t == 'roles':
            pdf_roles(pdf, no)
        elif t == 'gallery':
            pdf_gallery(pdf, no)
        elif t in ('security', 'integrations'):
            pdf_security_integrations(pdf, no, t)
        elif t == 'roadmap':
            pdf_roadmap(pdf, no)
        elif t == 'compare':
            pdf_compare(pdf, no)
        elif t == 'steps':
            pdf_steps(pdf, no)
        elif t == 'faq':
            pdf_faq(pdf, no, p)
        elif t == 'contact':
            pdf_contact(pdf, no)
    pdf.save(path, 'DT Medix Product Book')
    return len(BOOK)


# ---------------------------------------------------------------- HTML
CSS = """
:root{--blue:#0b63ce;--navy:#0a1428;--sky:#38a8ff;--slate:#4a5568;--cloud:#f5f7fa;--light:#eaeff7;--border:#dfe6f0;--green:#16915a;--red:#c83c3c}
*{box-sizing:border-box}
html{background:#d9e0ea}
body{margin:0;font-family:Helvetica,Arial,sans-serif;color:var(--navy);font-size:11px;line-height:1.5}
.page{width:210mm;height:297mm;margin:12px auto;background:#fff;position:relative;overflow:hidden;padding:20mm 15mm 18mm;box-shadow:0 2px 14px rgba(10,20,40,.18);page-break-after:always;break-after:page}
.page.dark{background:var(--navy);color:#fff;padding:0}
.top{position:absolute;left:0;right:0;top:0;height:2mm;background:var(--blue)}
.head{position:absolute;left:15mm;right:15mm;top:8mm;display:flex;justify-content:space-between;font-size:9px;color:var(--slate);border-bottom:1px solid var(--border);padding-bottom:3mm}
.head b{color:var(--blue)}
.foot{position:absolute;left:15mm;right:15mm;bottom:7mm;display:flex;justify-content:space-between;font-size:8.5px;color:var(--slate);border-top:1px solid var(--border);padding-top:3mm}
.foot b{color:var(--blue);font-size:10px}
.kicker{color:var(--blue);font-weight:700;font-size:9.5px;letter-spacing:.06em;text-transform:uppercase;margin:0}
h2{font-size:22px;line-height:1.2;margin:4px 0 10px}
h3{font-size:12.5px;margin:0 0 4px}
p{margin:0 0 7px;color:var(--slate);font-size:11px}
ul{margin:0 0 8px;padding:0;list-style:none}
li{position:relative;padding-left:13px;margin-bottom:4px;color:var(--slate)}
li:before{content:"";position:absolute;left:1px;top:6px;width:5px;height:5px;background:var(--blue)}
.cards{display:grid;grid-template-columns:1fr 1fr;gap:4mm;margin-bottom:6mm}
.card{background:var(--cloud);border:1px solid var(--border);border-radius:7px;padding:10px 12px}
.card h3:before{content:"";display:inline-block;width:6px;height:6px;border-radius:2px;background:var(--blue);margin-right:7px;vertical-align:1px}
.card p{font-size:10px;margin:0}
.cover{position:absolute;inset:0;background:var(--navy)}
.cover .band{position:absolute;left:0;right:0;top:0;height:152mm;background:var(--blue);border-bottom:2px solid var(--sky)}
.cover img.logo{position:absolute;left:15mm;top:20mm;width:88mm}
.cover .t{position:absolute;left:15mm;top:68mm;width:150mm}
.cover .t small{display:block;font-weight:700;letter-spacing:.1em;color:#bedcff;font-size:11px;margin-bottom:8px}
.cover .t h1{font-size:37px;line-height:1.14;margin:0 0 10px;color:#fff}
.cover .t p{color:#e1eeff;font-size:13px;width:150mm}
.cover .s1{position:absolute;left:25mm;top:183mm;width:74mm;border-radius:6px;box-shadow:0 6px 20px rgba(0,0,0,.4)}
.cover .s2{position:absolute;right:15mm;top:166mm;width:74mm;border-radius:6px;box-shadow:0 6px 20px rgba(0,0,0,.4)}
.cover .demo{position:absolute;left:15mm;bottom:26mm;border:1px solid var(--sky);border-radius:8px;padding:9px 14px;color:#fff}
.cover .demo small{display:block;color:var(--sky);font-weight:700;font-size:9px}
.cover .demo b{font-size:14px}
.cover .by{position:absolute;right:15mm;bottom:12mm;text-align:right;color:#fff;font-weight:700}
.cover .by small{display:block;font-weight:400;color:#bed2eb}
.toc{display:flex;justify-content:space-between;border-bottom:1px dotted var(--border);padding:5px 0;font-size:12px}
.toc b{color:var(--blue)}
.glance{background:var(--light);border-radius:9px;padding:14px 18px;margin:12px 0}
.glance h3{font-size:14px}
.facts{display:grid;grid-template-columns:repeat(3,1fr);gap:3mm}
.fact{border:1px solid var(--border);border-radius:7px;padding:8px 12px}
.fact small{display:block;color:var(--blue);font-weight:700;font-size:8.5px;text-transform:uppercase}
.fact b{font-size:12px}
.cols3{display:grid;grid-template-columns:repeat(3,1fr);gap:4mm;margin-bottom:8mm}
.flow{display:grid;grid-template-columns:1fr 1fr;gap:4mm;margin:10px 0 8mm}
.step{background:var(--cloud);border:1px solid var(--border);border-radius:9px;padding:12px 14px;min-height:34mm}
.step .n{display:inline-block;width:30px;height:30px;line-height:30px;text-align:center;border-radius:50%;background:var(--blue);color:#fff;font-weight:700;margin-right:10px}
.step b{font-size:14px}
.step p{margin-top:10px}
.mod{border:1px solid var(--border);border-radius:10px;height:126mm;padding:6mm;display:grid;grid-template-columns:1fr 78mm;gap:6mm;margin-bottom:5mm}
.mod h2{font-size:17px;margin:2px 0 6px}
.mod li{color:var(--navy);font-size:10px;margin-bottom:3px}
.mod .shot img{width:100%;border:1px solid var(--border);display:block}
.mod .shot small{display:block;font-size:8.5px;color:var(--slate);margin-top:4px}
table{width:100%;border-collapse:collapse}
th{background:var(--navy);color:#fff;padding:9px;font-size:10.5px}
th.hl{background:var(--blue)}
td,th.row{padding:9px 12px;border-bottom:1px solid var(--border);font-size:10.5px;text-align:left}
tr:nth-child(even) td,tr:nth-child(even) th.row{background:var(--cloud)}
td.c{text-align:center}
.ok{color:var(--green);font-weight:700}.no{color:var(--red);font-weight:700}
.roles td:first-child{font-weight:700;width:34mm}
.gal{display:grid;grid-template-columns:repeat(3,1fr);gap:4mm}
.gal figure{margin:0}
.gal .w{height:60mm;overflow:hidden;border:1px solid var(--border)}
.gal img{width:100%;display:block}
.gal figcaption{font-weight:700;font-size:10px;margin-top:4px}
.stepl{display:flex;gap:12px;margin-bottom:11px}
.stepl .n{flex:0 0 34px;height:34px;line-height:34px;text-align:center;border-radius:50%;background:var(--blue);color:#fff;font-weight:700}
.price{background:var(--blue);color:#fff;border-radius:10px;padding:16px 20px;margin-top:8px}
.price h3{font-size:22px;margin:0}
.price p,.price li{color:#dcebff}.price li:before{background:var(--sky)}
.price ul{columns:2}
.faq h3{font-size:13px;margin:0 0 4px}
.faq p{margin-bottom:14px;font-size:11.5px}
.side{display:grid;grid-template-columns:1fr 62mm;gap:6mm;align-items:start}
.side figure{margin:0}.side img{width:100%;border:1px solid var(--border);display:block}.side figcaption{font-size:8.5px;color:var(--slate);margin-top:4px}
.rm{display:grid;grid-template-columns:1fr 1fr;gap:4mm;margin-top:6mm}
.rm>div{border-radius:9px;padding:12px 16px}.rm b{font-size:10px;letter-spacing:.06em}
.rm .ready{background:var(--light)}.rm .ready b{color:var(--green)}.rm .ready li{color:var(--navy)}
.rm .next{background:var(--blue);color:#fff}.rm .next li{color:#fff}.rm .next li:before{background:var(--sky)}
.contact{padding:0}
.contact .band{background:var(--blue);height:106mm;padding:20mm 15mm 0}
.contact .band img{width:80mm}
.contact .band h1{font-size:30px;line-height:1.2;margin:16mm 0 8px;color:#fff}
.contact .band p{color:#e1eeff;font-size:13px}
.contact .rows{padding:12mm 15mm}
.contact small{display:block;color:var(--sky);font-weight:700;font-size:9.5px;letter-spacing:.05em;text-transform:uppercase}
.contact .rows div{margin-bottom:11px}
.contact b{font-size:16px;color:#fff}
.contact a{color:#fff;text-decoration:none}
@page{size:A4;margin:0}
@media print{html{background:none}.page{margin:0;box-shadow:none}}
"""


def e(s):
    return H.escape(str(s))


def head(no, title):
    return (f'<div class="top"></div><div class="head"><span><b>DT Medix</b> | Product book</span><span>{e(title)}</span></div>'
            f'<div class="foot"><span>Datatech BD Ltd. | datatechbd.com | {e(CONTACT["phone"])}</span><b>{no}</b></div>')


def cards_html(items):
    return '<div class="cards">' + ''.join(f'<div class="card"><h3>{e(t)}</h3><p>{e(d)}</p></div>' for t, d in items) + '</div>'


def ul(items):
    return '<ul>' + ''.join(f'<li>{e(i)}</li>' for i in items) + '</ul>'


def build_html(path):
    toc = toc_entries()
    out = ['<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">',
           '<title>DT Medix Product Book | Diagnostic Center, Pathology Lab and Clinic Management Software</title>',
           '<meta name="description" content="Product book for DT Medix, diagnostic center, pathology lab and clinic management software by Datatech BD Ltd.">',
           f'<style>{CSS}</style></head><body>']
    for i, p in enumerate(BOOK):
        no = i + 1
        t = p['t']
        b = []
        if t == 'cover':
            b.append(f'<section class="page dark"><div class="cover"><div class="band"></div><img class="logo" src="assets/logo-primary-reversed.png" alt="DT Medix logo">'
                     '<div class="t"><small>PRODUCT BOOK</small><h1>Diagnostic Center, Pathology Lab &amp; Clinic Management Software</h1>'
                     '<p>One cloud system for patients, appointments, billing, barcode samples, lab results, staff attendance and payroll. Built for Bangladesh, in Bangla and English.</p></div>'
                     '<img class="s1" src="assets/worklist.jpg" alt="DT Medix lab worklist"><img class="s2" src="assets/reception.jpg" alt="DT Medix reception counter">'
                     f'<a class="demo" href="{CONTACT["demo"]}"><small>Free live demo</small><b>dt-medix.datatechbd.com</b></a>'
                     '<div class="by">A product of Datatech BD Ltd.<small>Software that runs business.</small></div></div></section>')
            out += b
            continue
        if t == 'contact':
            out.append(f'<section class="page dark contact"><div class="band"><img src="assets/logo-primary-reversed.png" alt="DT Medix logo"><h1>See it with your own tests,<br>doctors and branches.</h1><p>Open the free live demo, then talk to our sales team.</p></div><div class="rows">'
                        f'<div><small>Free live demo</small><b><a href="{CONTACT["demo"]}">dt-medix.datatechbd.com</a></b></div>'
                        f'<div><small>Email</small><b><a href="mailto:{CONTACT["email"]}">{CONTACT["email"]}</a></b></div>'
                        f'<div><small>Phone</small><b><a href="tel:+8801575888775">{CONTACT["phone"]}</a></b></div>'
                        f'<div><small>WhatsApp</small><b><a href="https://wa.me/8801575888775">{CONTACT["whatsapp"]}</a></b></div>'
                        f'<div><small>Website</small><b><a href="{CONTACT["web"]}">datatechbd.com/products/dt-medix</a></b></div>'
                        f'<div><small>Address</small><b style="font-size:13px;font-weight:400">{e(CONTACT["address"])}</b></div>'
                        '<div style="margin-top:14mm"><b style="font-size:13px">Datatech BD Ltd.</b><br><span style="color:#bed2eb">Software that runs business.</span></div></div></section>')
            continue
        title = TITLES.get(t, page_title(i, p))
        body = ''
        if t == 'about':
            body = ('<p class="kicker">Introduction</p><h2>What DT Medix is, and who builds it</h2>'
                    '<p>DT Medix is diagnostic center, pathology lab and clinic management software. It follows a patient through the real flow of a lab, from registration and appointment to the invoice, the barcode sample, the lab result and the report, and it also runs the staff side with attendance, leave and payroll.</p>'
                    '<p>DT Medix is built by Datatech BD Ltd., a Dhaka software company. Each center receives its own installation and database, so patient data is never mixed with another business. The whole interface works in English and Bangla.</p>'
                    '<p class="kicker" style="margin-top:14mm">Contents</p><h2>What is inside this book</h2>'
                    + ''.join(f'<div class="toc"><span>{e(l)}</span><b>{n}</b></div>' for l, n in toc))
        elif t == 'overview':
            body = (f'<p class="kicker">{e(J["overview"]["kicker"])}</p><h2>{e(J["overview"]["title"])}</h2>'
                    + ''.join(f'<p>{e(x)}</p>' for x in J['overview']['body'])
                    + f'<div class="glance"><h3>{e(J["overview"]["glance_title"])}</h3>{ul(J["overview"]["glance"])}</div>'
                    + '<div class="facts">' + ''.join(f'<div class="fact"><small>{e(f["label"])}</small><b>{e(f["value"])}</b></div>' for f in J['facts']) + '</div>')
        elif t == 'problems':
            body = (f'<p class="kicker">{e(J["problems"]["kicker"])}</p><h2>{e(J["problems"]["title"])}</h2><p>{e(J["problems"]["description"])}</p>'
                    + cards_html([(x['title'], x['desc']) for x in PROBLEMS])
                    + '<p class="kicker">How we answer</p><h2>Each problem, and the DT Medix answer</h2><table class="roles"><tr><th>PROBLEM</th><th>ANSWER</th></tr>'
                    + ''.join(f'<tr><td>{e(a)}</td><td>{e(b_)}</td></tr>' for a, b_ in ANSWERS) + '</table>')
        elif t == 'benefits':
            body = (f'<p class="kicker">{e(J["benefits"]["kicker"])}</p><h2>{e(J["benefits"]["title"])}</h2><p>{e(J["benefits"]["description"])}</p><div class="cols3">'
                    + ''.join(f'<div class="card"><h3>{e(c_["title"])}</h3>{ul(c_["points"])}</div>' for c_ in J['benefits']['columns']) + '</div>'
                    + f'<p class="kicker">{e(J["audience"]["kicker"])}</p><h2>{e(J["audience"]["title"])}</h2>'
                    + cards_html([(x['title'], x['desc']) for x in J['audience']['items']]))
        elif t == 'flow':
            body = ('<p class="kicker">Workflow</p><h2>From the front desk to the finished report</h2>'
                    '<p>Every step uses the same patient, order and sample records, so nothing is typed twice and every handover is visible.</p><div class="flow">'
                    + ''.join(f'<div class="step"><span class="n">{n + 1}</span><b>{e(a)}</b><p>{e(d)}</p></div>' for n, (a, d) in enumerate(FLOW)) + '</div>'
                    '<p class="kicker">Behind the counter</p><h2>The business side runs on the same data</h2>'
                    + ul(['Discounts, refunds and cancellations after collection wait for a manager approval, with a reason on record.',
                          'Staff punch in at a ZKTeco fingerprint device, or a manager marks attendance by hand.',
                          'Late minutes, overtime, leave and holidays feed the monthly payroll and printable payslips.',
                          'Owners switch between branches; branch staff only ever see their own branch.']))
        elif t == 'modules':
            for idx in p['items']:
                m = MODS[idx]
                body += (f'<div class="mod"><div><p class="kicker">Feature {idx + 1:02d}</p><h2>{e(m["title"])}</h2><p>{e(m["desc"])}</p>{ul(m["points"])}</div>'
                         f'<div class="shot"><img src="assets/{m["img"]}.jpg" alt="{e(m["alt"])}"><small>{e(m["alt"])}</small></div></div>')
        elif t == 'roles':
            body = ('<p class="kicker">Access control</p><h2>Eight roles, each with its own menu and actions</h2>'
                    '<p>Menus, buttons and actions follow permissions. Branch staff only see their own branch, and every record view and override is logged.</p>'
                    '<table class="roles"><tr><th>ROLE</th><th>WHAT THEY DO</th></tr>' + ''.join(f'<tr><td>{e(a)}</td><td>{e(b_)}</td></tr>' for a, b_ in ROLES) + '</table>'
                    '<p class="kicker" style="margin-top:8mm">Setup</p><h2>Branches and data separation</h2>'
                    + ul(['One installation can hold many branches, each with its own staff, orders and samples.',
                          'Optional branch price lists let each branch charge its own test prices.',
                          'Cross-branch access is blocked and covered by automated tests.',
                          'Optional two-step verification for staff sign-in.']))
        elif t == 'gallery':
            names = [('patients', 'Patient list'), ('orders', 'Test orders and invoices'), ('packages', 'Health packages'),
                     ('doctors', 'Doctors and fees'), ('doctor-detail', 'Doctor weekly schedule'), ('sample-receive', 'Receive samples'),
                     ('refunds', 'Refund requests'), ('leave', 'Leave requests'), ('hr-setup', 'Shifts and ZKTeco devices')]
            body = ('<p class="kicker">Screenshots</p><h2>More screens from the live demo</h2><p>Real screens with sample data. Open the free demo at dt-medix.datatechbd.com to click through every one.</p><div class="gal">'
                    + ''.join(f'<figure><div class="w"><img src="assets/{n}.jpg" alt="DT Medix: {e(cp)}"></div><figcaption>{e(cp)}</figcaption></figure>' for n, cp in names) + '</div>')
        elif t == 'security':
            body = (f'<p class="kicker">{e(J["security"]["kicker"])}</p><h2>{e(J["security"]["title"])}</h2>' + cards_html([(x['title'], x['desc']) for x in J['security']['items']])
                    + '<p class="kicker">Data protection</p><h2>Patient data is handled with care</h2>'
                    + '<div class="side"><div>' + ul(['Message templates never contain names, results or diagnoses.', 'Released reports are never edited in place; a correction is a new version.',
                          'Every patient record view is stored with user, time and address.', 'Idle sessions sign out automatically.'])
                    + '</div><figure><img src="assets/access-log.jpg" alt="DT Medix patient access log"><figcaption>Patient access log with user, time and action</figcaption></figure></div>')
        elif t == 'integrations':
            body = (f'<p class="kicker">{e(J["integrations"]["kicker"])}</p><h2>{e(J["integrations"]["title"])}</h2><p>{e(J["integrations"]["description"])}</p>'
                    + cards_html([(x['title'], x['desc']) for x in J['integrations']['items']])
                    + '<p class="kicker">Hardware</p><h2>What you need at the counter</h2>'
                    + '<div class="side"><div>' + ul(['A computer, tablet or phone with a modern web browser.', 'A thermal (80 mm) or A4 printer for invoices and receipts.',
                          'A label printer for sample barcodes (optional).', 'A ZKTeco attendance device with push support (optional).'])
                    + '</div><figure><img src="assets/invoice-print.jpg" alt="DT Medix printed invoice"><figcaption>Printed invoice and money receipt</figcaption></figure></div>')
        elif t == 'roadmap':
            body = (f'<p class="kicker">{e(J["roadmap"]["kicker"])}</p><h2>{e(J["roadmap"]["title"])}</h2><p>{e(J["roadmap"]["description"])}</p>'
                    + cards_html([(x['title'], x['desc']) for x in J['roadmap']['items']]) + f'<p>{e(J["roadmap"]["note"])}</p>'
                    + '<div class="rm"><div class="ready"><b>READY TODAY</b>' + ul(READY) + '</div><div class="next"><b>COMING NEXT</b>' + ul([x['title'] for x in J['roadmap']['items']]) + '</div></div>')
        elif t == 'compare':
            cols = J['compare']['cols']
            body = (f'<p class="kicker">{e(J["compare"]["kicker"])}</p><h2>{e(J["compare"]["title"])}</h2><p>{e(J["compare"]["description"])}</p><table><tr><th></th>'
                    + ''.join(f'<th class="{"hl" if k == 2 else ""}">{e(c_)}</th>' for k, c_ in enumerate(cols)) + '</tr>'
                    + ''.join(f'<tr><th class="row">{e(l)}</th>' + ''.join(f'<td class="c {"ok" if v else "no"}">{"&#10003;" if v else "&#10007;"}</td>' for v in vals) + '</tr>' for l, vals in J['compare']['rows']) + '</table>')
        elif t == 'steps':
            body = (f'<p class="kicker">{e(J["steps"]["kicker"])}</p><h2>{e(J["steps"]["title"])}</h2>'
                    + ''.join(f'<div class="stepl"><span class="n">{n + 1}</span><div><h3>{e(s["title"])}</h3><p style="margin:0">{e(s["desc"])}</p></div></div>' for n, s in enumerate(J['steps']['items']))
                    + f'<p class="kicker" style="margin-top:6mm">{e(J["pricing"]["kicker"])}</p><h2>{e(J["pricing"]["title"])}</h2><p>{e(J["pricing"]["description"])}</p>'
                    + f'<div class="price"><h3>{e(J["pricing"]["price"])}</h3><p>{e(J["pricing"]["unit"])}. {e(J["pricing"]["regular"])}.</p>{ul(J["pricing"]["included"])}'
                    f'<p><b style="color:#fff">Free demo first. Sales: {e(CONTACT["phone"])} | {e(CONTACT["email"])}</b></p></div>')
        elif t == 'faq':
            body = (f'<p class="kicker">{"FAQ" if p["items"][0] == 0 else "FAQ (continued)"}</p><h2>Questions owners ask us</h2><div class="faq">'
                    + ''.join(f'<h3>{e(FAQ[k]["q"])}</h3><p>{e(FAQ[k]["a"])}</p>' for k in p['items']) + '</div>')
        out.append(f'<section class="page">{head(no, title)}{body}</section>')
    out.append('</body></html>')
    open(path, 'w').write('\n'.join(out))


def copy_assets():
    os.makedirs(f'{OUT}/assets', exist_ok=True)
    names = set(m['img'] for m in MODS) | {'access-log', 'invoice-print', 'patients', 'orders', 'packages', 'doctors', 'doctor-detail', 'sample-receive', 'refunds', 'leave', 'hr-setup', 'reception', 'worklist'}
    for n in names:
        shutil.copy(shot(n), f'{OUT}/assets/{n}.jpg')
    shutil.copy(f'{LOGOS}/logo-primary-reversed-1600x420.png', f'{OUT}/assets/logo-primary-reversed.png')


if __name__ == '__main__':
    os.makedirs(OUT, exist_ok=True)
    copy_assets()
    n = build_pdf(f'{OUT}/dt-medix-product-book-en.pdf')
    build_html(f'{OUT}/dt-medix-product-book-en.html')
    print('pages', n)
