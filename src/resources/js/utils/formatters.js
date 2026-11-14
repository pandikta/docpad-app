/**
 * Format ukuran file (bytes) menjadi teks yang mudah dibaca. Contoh: 2048 -> "2 KB"
 */
export function formatFileSize(bytes) {
    if (!bytes) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / Math.pow(1024, index);

    return `${Number.isInteger(value) ? value : value.toFixed(1)} ${units[index]}`;
}

/**
 * Format tanggal ISO menjadi "Oct 13, 2026 14:30"
 */
export function formatDateTime(isoString) {
    if (!isoString) return '-';

    const date = new Date(isoString);
    const datePart = date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    const timePart = date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

    return `${datePart} ${timePart}`;
}

/**
 * Ambil inisial dari nama. Contoh: "John Doe" -> "JD"
 */
export function getInitials(name = '') {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0].toUpperCase())
        .join('');
}
