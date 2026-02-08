/**
 * Converts a given number of milliseconds into a specified format of time measurement or a breakdown of days, hours, minutes, and seconds.
 *
 * @param {number} milliseconds - The number of milliseconds to be converted.
 * @return { d: number, h: number, m: number, s: number, total: { h: number, m: number, s: number }} - An object
 * containing days, hours, minutes, and seconds if with the total values.
 */
export function convertMilliseconds(milliseconds: number): {
    d: number;
    h: number;
    m: number;
    s: number;
    total: { h: number; m: number; s: number };
} {
    const total_seconds = parseInt(
        Math.floor(milliseconds / 1000).toString(10),
        10,
    );
    const total_minutes = parseInt(
        Math.floor(total_seconds / 60).toString(10),
        10,
    );
    const total_hours = parseInt(
        Math.floor(total_minutes / 60).toString(10),
        10,
    );

    const days = parseInt(Math.floor(total_hours / 24).toString(10), 10);
    const seconds = parseInt((total_seconds % 60).toString(10), 10);
    const minutes = parseInt((total_minutes % 60).toString(10), 10);
    const hours = parseInt((total_hours % 24).toString(10), 10);

    return {
        d: days,
        h: hours,
        m: minutes,
        s: seconds,
        total: { h: total_hours, m: total_minutes, s: total_seconds },
    };
}

export function convertDuration(start: Date, until?: Date | undefined): string {
    const end: Date = until ? until : new Date();

    const diff = convertMilliseconds(end.getTime() - start.getTime());

    if (diff.d > 0) {
        return `${diff.d}d ${diff.h.toString(10).padStart(2, '0')}h ${diff.m.toString(10).padStart(2, '0')}m ${diff.s.toString(10).padStart(2, '0')}s`;
    }
    if (diff.h > 0) {
        return `${diff.h.toString(10).padStart(2, '0')}h ${diff.m.toString(10).padStart(2, '0')}m ${diff.s.toString(10).padStart(2, '0')}s`;
    }
    if (diff.m > 0) {
        return `${diff.m.toString(10).padStart(2, '0')}m ${diff.s.toString(10).padStart(2, '0')}s`;
    }
    if (diff.s > 0) {
        return `${diff.s.toString(10).padStart(2, '0')}s`;
    }

    return diff.total.s.toString();
}
