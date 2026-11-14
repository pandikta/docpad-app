import { ref } from 'vue';

// State dibuat di luar fungsi supaya dibagi antar komponen (Topbar <-> DocumentTable).
const searchQuery = ref('');

export function useDocumentSearch() {
    const clearSearch = () => {
        searchQuery.value = '';
    };

    /**
     * Cek apakah dokumen cocok dengan kata kunci pencarian
     * (mencakup nama file, pembuat dokumen, dan isi teks di dalam dokumen).
     */
    const matchesDocument = (doc) => {
        const keyword = searchQuery.value.trim().toLowerCase();
        if (!keyword) return true;

        const name = (doc.name ?? '').toLowerCase();
        const createdBy = (doc.created_by ?? '').toLowerCase();
        const plainContent = (doc.content ?? '')
            .replace(/<[^>]*>/g, ' ')
            .toLowerCase();

        return (
            name.includes(keyword) ||
            createdBy.includes(keyword) ||
            plainContent.includes(keyword)
        );
    };

    return {
        searchQuery,
        clearSearch,
        matchesDocument,
    };
}
