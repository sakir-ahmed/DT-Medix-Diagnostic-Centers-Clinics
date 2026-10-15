/**
 * Result flag rules (mirror of App\Support\ResultFlagger on the server):
 * critical when beyond critical limits, H / L when outside the reference
 * range, N otherwise. The server recalculates and stores the snapshot.
 */
export function computeFlag(value, range) {
    if (value === null || value === undefined || value === '' || !range) {
        return null;
    }
    if (range.result_type === 'choice' || range.result_type === 'text') {
        if (range.abnormal_values?.length && range.abnormal_values.map((v) => String(v).toLowerCase()).includes(String(value).toLowerCase())) {
            return 'A';
        }
        return range.result_type === 'choice' ? 'N' : null;
    }
    const number = Number(value);
    if (!Number.isFinite(number)) {
        return null;
    }
    const num = (v) => (v === null || v === undefined || v === '' ? null : Number(v));
    const [criticalLow, criticalHigh, low, high] = [num(range.critical_low), num(range.critical_high), num(range.low), num(range.high)];

    if ((criticalLow !== null && number <= criticalLow) || (criticalHigh !== null && number >= criticalHigh)) {
        return 'C';
    }
    if (low !== null && number < low) {
        return 'L';
    }
    if (high !== null && number > high) {
        return 'H';
    }
    return 'N';
}

export function deltaExceeded(value, previous, percent) {
    if (value === null || value === undefined || value === '') {
        return false;
    }
    const current = Number(value);
    const before = Number(previous);
    if (!percent || !Number.isFinite(current) || !Number.isFinite(before) || before === 0 || previous === null || previous === '') {
        return false;
    }
    return (Math.abs(current - before) / Math.abs(before)) * 100 > Number(percent);
}

/**
 * Evaluate a calculated parameter formula such as "{TC} - {HDL} - {TG} / 5".
 * Only numbers, parameter codes and + - * / ( ) are allowed.
 */
export function evaluateFormula(formula, valuesByCode) {
    if (!formula) {
        return null;
    }
    let missing = false;
    const expression = formula.replace(/\{([A-Za-z0-9_]+)\}/g, (_, code) => {
        const v = Number(valuesByCode[code]);
        if (valuesByCode[code] === undefined || valuesByCode[code] === '' || !Number.isFinite(v)) {
            missing = true;
            return '0';
        }
        return `(${v})`;
    });
    if (missing || !/^[\d\s.+\-*/()]+$/.test(expression)) {
        return null;
    }
    try {
        // eslint-disable-next-line no-new-func
        const result = Function(`"use strict"; return (${expression});`)();
        return Number.isFinite(result) ? Math.round(result * 100) / 100 : null;
    } catch {
        return null;
    }
}
