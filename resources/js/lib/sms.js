/**
 * SMS segments: 160/153 characters for GSM text, 70/67 for Unicode (Bangla).
 * Mirrors App\Services\SmsService::segments().
 */
export function smsSegments(text) {
    const length = [...(text ?? '')].length;
    if (length === 0) {
        return { length: 0, segments: 0, unicode: false };
    }
    const unicode = /[^\x00-\x7F]/.test(text);
    const [single, multi] = unicode ? [70, 67] : [160, 153];

    return { length, unicode, segments: length <= single ? 1 : Math.ceil(length / multi) };
}

export function renderTemplate(body, values) {
    return (body ?? '').replace(/\{([a-z_]+)\}/g, (_, key) => values[key] ?? '').replace(/\s{2,}/g, ' ').trim();
}
