import { ref } from 'vue';

export type Locale = 'en' | 'id';

const STORAGE_KEY = 'youritem.locale';
const initialLocale = localStorage.getItem(STORAGE_KEY) === 'id' ? 'id' : 'en';
const locale = ref<Locale>(initialLocale);

const messages: Record<Locale, Record<string, string>> = {
    en: {
        'language.english': 'English',
        'language.indonesian': 'Bahasa Indonesia',
        'language.label': 'Language',
        'nav.dashboard': 'Dashboard',
        'nav.home': 'Home',
        'nav.wishlist': 'Wishlist',
        'nav.categories': 'Categories',
        'nav.budget': 'Budget',
        'nav.shopping': 'Shopping',
        'nav.purchases': 'Purchase History',
        'nav.history': 'History',
        'nav.settings': 'Profile & Settings',
        'nav.profile': 'Profile',
        'nav.account': 'Account',
        'auth.logout': 'Log out',
        'auth.welcome': 'Welcome back',
        'auth.login_subtitle': 'Sign in to plan what to buy next.',
        'auth.sign_in': 'Sign In',
        'auth.signing_in': 'Signing in...',
        'auth.no_account': "Don't have an account?",
        'auth.create_account': 'Create account',
        'auth.create_title': 'Create your account',
        'auth.create_subtitle': 'Start planning what to buy next.',
        'auth.creating': 'Creating account...',
        'auth.have_account': 'Already have an account?',
        'auth.name': 'Name',
        'auth.email': 'Email',
        'auth.password': 'Password',
        'auth.confirm_password': 'Confirm Password',
        'auth.password_mismatch': 'Password confirmation does not match.',
        'auth.login_error': 'Unable to sign in. Please try again.',
        'auth.register_error':
            'Unable to create your account. Please try again.',
        'common.cancel': 'Cancel',
        'common.save': 'Save',
        'common.saving': 'Saving...',
        'common.edit': 'Edit',
        'common.delete': 'Delete',
        'common.deleting': 'Deleting...',
        'common.active': 'Active',
        'common.inactive': 'Inactive',
        'common.retry': 'Try Again',
        'common.error': 'Something went wrong.',
        'common.connection_error':
            'Please check your connection and try again.',
        'categories.title': 'Categories',
        'categories.subtitle':
            'Create and manage how your wishlist is organised.',
        'categories.add': 'Add category',
        'categories.empty': 'No categories yet.',
        'categories.empty_description':
            'Create a category to start organising your wishlist.',
        'categories.load_error': "We couldn't load the categories.",
        'categories.available_hint': 'Available when adding new items',
        'categories.inactive_hint': 'Kept for existing items only',
        'categories.name': 'Category name',
        'categories.create_title': 'Create category',
        'categories.edit_title': 'Edit category',
        'categories.create': 'Create category',
        'categories.creating': 'Creating...',
        'categories.created': 'Category created.',
        'categories.updated': 'Category updated.',
        'categories.deleted': 'Category deleted.',
        'categories.status_help':
            'Inactive categories remain attached to existing wishlist items.',
        'categories.delete_title': 'Delete category?',
        'categories.delete_message':
            'Delete “:name”? This action cannot be undone.',
        'categories.delete_used_error':
            'This category is still used by wishlist items. Deactivate it instead.',
        'settings.title': 'Profile & Settings',
        'settings.subtitle': 'Your account at a glance.',
        'settings.account_type': 'Personal planner',
        'settings.currency': 'Currency',
        'settings.data': 'Data',
        'settings.data_value': 'Private to your account',
    },
    id: {
        'language.english': 'English',
        'language.indonesian': 'Bahasa Indonesia',
        'language.label': 'Bahasa',
        'nav.dashboard': 'Dasbor',
        'nav.home': 'Beranda',
        'nav.wishlist': 'Daftar Keinginan',
        'nav.categories': 'Kategori',
        'nav.budget': 'Anggaran',
        'nav.shopping': 'Belanja',
        'nav.purchases': 'Riwayat Pembelian',
        'nav.history': 'Riwayat',
        'nav.settings': 'Profil & Pengaturan',
        'nav.profile': 'Profil',
        'nav.account': 'Akun',
        'auth.logout': 'Keluar',
        'auth.welcome': 'Selamat datang kembali',
        'auth.login_subtitle': 'Masuk untuk merencanakan pembelian berikutnya.',
        'auth.sign_in': 'Masuk',
        'auth.signing_in': 'Sedang masuk...',
        'auth.no_account': 'Belum punya akun?',
        'auth.create_account': 'Buat akun',
        'auth.create_title': 'Buat akun Anda',
        'auth.create_subtitle': 'Mulai rencanakan pembelian berikutnya.',
        'auth.creating': 'Membuat akun...',
        'auth.have_account': 'Sudah punya akun?',
        'auth.name': 'Nama',
        'auth.email': 'Email',
        'auth.password': 'Kata sandi',
        'auth.confirm_password': 'Konfirmasi kata sandi',
        'auth.password_mismatch': 'Konfirmasi kata sandi tidak cocok.',
        'auth.login_error': 'Tidak dapat masuk. Silakan coba lagi.',
        'auth.register_error': 'Tidak dapat membuat akun. Silakan coba lagi.',
        'common.cancel': 'Batal',
        'common.save': 'Simpan',
        'common.saving': 'Menyimpan...',
        'common.edit': 'Ubah',
        'common.delete': 'Hapus',
        'common.deleting': 'Menghapus...',
        'common.active': 'Aktif',
        'common.inactive': 'Nonaktif',
        'common.retry': 'Coba Lagi',
        'common.error': 'Terjadi kesalahan.',
        'common.connection_error': 'Periksa koneksi Anda lalu coba lagi.',
        'categories.title': 'Kategori',
        'categories.subtitle':
            'Buat dan kelola pengelompokan daftar keinginan Anda.',
        'categories.add': 'Tambah kategori',
        'categories.empty': 'Belum ada kategori.',
        'categories.empty_description':
            'Buat kategori untuk mulai mengelompokkan daftar keinginan.',
        'categories.load_error': 'Kategori tidak dapat dimuat.',
        'categories.available_hint': 'Tersedia saat menambahkan item baru',
        'categories.inactive_hint':
            'Hanya dipertahankan untuk item yang sudah ada',
        'categories.name': 'Nama kategori',
        'categories.create_title': 'Buat kategori',
        'categories.edit_title': 'Ubah kategori',
        'categories.create': 'Buat kategori',
        'categories.creating': 'Membuat...',
        'categories.created': 'Kategori berhasil dibuat.',
        'categories.updated': 'Kategori berhasil diperbarui.',
        'categories.deleted': 'Kategori berhasil dihapus.',
        'categories.status_help':
            'Kategori nonaktif tetap terhubung dengan item daftar keinginan yang sudah ada.',
        'categories.delete_title': 'Hapus kategori?',
        'categories.delete_message':
            'Hapus “:name”? Tindakan ini tidak dapat dibatalkan.',
        'categories.delete_used_error':
            'Kategori ini masih dipakai oleh item daftar keinginan. Nonaktifkan saja.',
        'settings.title': 'Profil & Pengaturan',
        'settings.subtitle': 'Ringkasan akun Anda.',
        'settings.account_type': 'Perencana pribadi',
        'settings.currency': 'Mata uang',
        'settings.data': 'Data',
        'settings.data_value': 'Pribadi untuk akun Anda',
    },
};

function setLocale(value: Locale): void {
    locale.value = value;
    localStorage.setItem(STORAGE_KEY, value);
    document.documentElement.lang = value;
}

function t(
    key: string,
    replacements: Record<string, string | number> = {},
): string {
    let message = messages[locale.value][key] ?? messages.en[key] ?? key;

    Object.entries(replacements).forEach(([name, value]) => {
        message = message.replaceAll(`:${name}`, String(value));
    });

    return message;
}

document.documentElement.lang = locale.value;

export function useI18n() {
    return { locale, setLocale, t };
}
