export function formatIdr(amount: number): string {
    return `Rp${new Intl.NumberFormat('id-ID').format(amount)}`;
}
