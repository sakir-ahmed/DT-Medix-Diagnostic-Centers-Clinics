import { usePage } from '@inertiajs/vue3';

const BN_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
const MONTHS_EN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const MONTHS_BN = ['জানু', 'ফেব্রু', 'মার্চ', 'এপ্রি', 'মে', 'জুন', 'জুলা', 'আগ', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'];
const TIME_ZONE = 'Asia/Dhaka';

function currentLocale() {
    try {
        return usePage().props.app?.locale ?? 'en';
    } catch {
        return 'en';
    }
}

export function localizeDigits(value, locale = currentLocale()) {
    const text = String(value ?? '');

    return locale === 'bn' ? text.replace(/\d/g, (digit) => BN_DIGITS[Number(digit)]) : text;
}

/**
 * Bangladesh grouping: 12,34,567.00
 */
export function groupBd(integerPart) {
    const digits = integerPart.replace(/^-/, '');
    if (digits.length <= 3) {
        return digits;
    }
    const last3 = digits.slice(-3);
    const rest = digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');

    return `${rest},${last3}`;
}

export function formatNumber(value, decimals = 0, locale = currentLocale()) {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    const fixed = Number(value).toFixed(decimals);
    const negative = fixed.startsWith('-') && Number(fixed) !== 0;
    const [integer, fraction] = fixed.replace('-', '').split('.');
    const grouped = groupBd(integer) + (fraction ? `.${fraction}` : '');

    return localizeDigits((negative ? '-' : '') + grouped, locale);
}

export function formatMoney(value, { decimals = 2, symbol = true, locale = currentLocale() } = {}) {
    const formatted = formatNumber(value ?? 0, decimals, locale);

    return symbol ? `৳${formatted}` : formatted;
}

/**
 * Compact form for dashboard cards: 12.3L / 1.2Cr (লাখ / কোটি in Bangla).
 */
/**
 * Human-readable file size: "1.2 MB", "340 KB".
 */
export function formatFileSize(bytes, locale = currentLocale()) {
    const value = Number(bytes ?? 0);

    if (value < 1024) {
        return formatNumber(value, 0, locale) + ' B';
    }

    const units = ['KB', 'MB', 'GB'];
    let size = value;
    let unit = -1;

    do {
        size /= 1024;
        unit += 1;
    } while (size >= 1024 && unit < units.length - 1);

    return `${formatNumber(size, 1, locale)} ${units[unit]}`;
}

export function formatCompactMoney(value, locale = currentLocale()) {
    const amount = Number(value ?? 0);
    const abs = Math.abs(amount);
    const units = locale === 'bn' ? { cr: ' কোটি', l: ' লাখ', k: ' হাজার' } : { cr: 'Cr', l: 'L', k: 'K' };
    let text;

    if (abs >= 1e7) {
        text = `${(amount / 1e7).toFixed(1)}${units.cr}`;
    } else if (abs >= 1e5) {
        text = `${(amount / 1e5).toFixed(1)}${units.l}`;
    } else if (abs >= 1e3) {
        text = `${(amount / 1e3).toFixed(1)}${units.k}`;
    } else {
        text = amount.toFixed(0);
    }

    return `৳${localizeDigits(text, locale)}`;
}

function dhakaParts(value) {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
        return null;
    }
    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: TIME_ZONE,
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: false,
    }).formatToParts(date);
    const get = (type) => Number(parts.find((part) => part.type === type)?.value ?? 0);

    return { year: get('year'), month: get('month'), day: get('day'), hour: get('hour') % 24, minute: get('minute') };
}

/**
 * 15 Oct 2026 or 15 Oct 2026, 2:30 PM (Asia/Dhaka).
 */
export function formatDate(value, { time = false, locale = currentLocale() } = {}) {
    if (!value) {
        return '';
    }
    const parts = dhakaParts(value);
    if (!parts) {
        return '';
    }
    const months = locale === 'bn' ? MONTHS_BN : MONTHS_EN;
    let text = `${parts.day} ${months[parts.month - 1]} ${parts.year}`;

    if (time) {
        const hour12 = parts.hour % 12 === 0 ? 12 : parts.hour % 12;
        const suffix = parts.hour < 12 ? 'AM' : 'PM';
        text += `, ${hour12}:${String(parts.minute).padStart(2, '0')} ${suffix}`;
    }

    return localizeDigits(text, locale);
}

/**
 * A "HH:MM" or "HH:MM:SS" clock string as 12-hour time: 09:30 -> 9:30 AM.
 */
export function formatClock(value, locale = currentLocale()) {
    const match = /^(\d{1,2}):(\d{2})/.exec(String(value ?? ''));
    if (!match) {
        return '';
    }
    const hour = Number(match[1]);
    const hour12 = hour % 12 === 0 ? 12 : hour % 12;

    return localizeDigits(`${hour12}:${match[2]} ${hour < 12 ? 'AM' : 'PM'}`, locale);
}

export function formatTime(value, locale = currentLocale()) {
    const parts = dhakaParts(value);
    if (!parts) {
        return '';
    }
    const hour12 = parts.hour % 12 === 0 ? 12 : parts.hour % 12;

    return localizeDigits(`${hour12}:${String(parts.minute).padStart(2, '0')} ${parts.hour < 12 ? 'AM' : 'PM'}`, locale);
}

export function relativeTime(value, t, locale = currentLocale()) {
    const date = new Date(value);
    const seconds = Math.round((Date.now() - date.getTime()) / 1000);
    const abs = Math.abs(seconds);
    let text;

    if (abs < 60) {
        text = t('time.just_now');
    } else if (abs < 3600) {
        text = t('time.minutes_ago', { count: Math.round(abs / 60) });
    } else if (abs < 86400) {
        text = t('time.hours_ago', { count: Math.round(abs / 3600) });
    } else {
        return formatDate(value, { time: true, locale });
    }

    return localizeDigits(text, locale);
}

export function formatDuration(minutes, t, locale = currentLocale()) {
    const sign = minutes < 0 ? '-' : '';
    const abs = Math.abs(Math.round(minutes));
    const h = Math.floor(abs / 60);
    const m = abs % 60;
    const text = h > 0 ? t('time.hours_minutes', { hours: h, minutes: m }) : t('time.minutes', { count: m });

    return localizeDigits(sign + text, locale);
}

/**
 * "32 Y 4 M" from a date of birth.
 */
export function formatAge(dateOfBirth, t, locale = currentLocale()) {
    if (!dateOfBirth) {
        return '';
    }
    const dob = new Date(dateOfBirth);
    const now = new Date();
    let months = (now.getFullYear() - dob.getFullYear()) * 12 + (now.getMonth() - dob.getMonth());
    if (now.getDate() < dob.getDate()) {
        months -= 1;
    }
    const years = Math.floor(months / 12);
    const rest = months % 12;

    if (years < 1) {
        const days = Math.floor((now - dob) / 86400000);
        return localizeDigits(months < 1 ? t('patients.age_days', { days }) : t('patients.age_months', { months }), locale);
    }

    return localizeDigits(t('patients.age_years_months', { years, months: rest }), locale);
}
