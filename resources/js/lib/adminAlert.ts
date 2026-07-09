type AdminAlertPayload = {
    type: 'success' | 'error' | 'warning';
    title: string;
    message: string;
};

type AdminAlertListener = (payload: AdminAlertPayload) => void;

const listeners = new Set<AdminAlertListener>();

export function subscribeAdminAlert(listener: AdminAlertListener): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

function emit(payload: AdminAlertPayload) {
    listeners.forEach((listener) => listener(payload));
}

export const adminAlert = {
    success(message: string, title = 'Berhasil') {
        emit({ type: 'success', title, message });
    },
    error(message: string, title = 'Terjadi Kesalahan') {
        emit({ type: 'error', title, message });
    },
};

export type { AdminAlertPayload };
