window.SIMPATI = {
    apiBase: '/api/v1',

    getToken() {
        return localStorage.getItem('simpati_token');
    },

    setToken(token) {
        localStorage.setItem('simpati_token', token);
    },

    clearToken() {
        localStorage.removeItem('simpati_token');
        localStorage.removeItem('simpati_role');
        localStorage.removeItem('simpati_user_id');
    },

    isLoggedIn() {
        return !!this.getToken();
    },

    authHeaders(useAuth = true) {
        const headers = {
            'Content-Type': 'application/json',
        };

        if (useAuth && this.isLoggedIn()) {
            headers.Authorization = `Bearer ${this.getToken()}`;
        }

        return headers;
    },

    async request(path, options = {}) {
        const config = {
            method: options.method || 'GET',
            headers: this.authHeaders(options.auth !== false),
        };

        if (options.body) {
            config.body = JSON.stringify(options.body);
        }

        const url = `${this.apiBase}${path}`;
        const response = await fetch(url, config);
        const payload = await response.json().catch(() => null);

        if (!response.ok) {
            const message = payload?.message || 'Terjadi kesalahan jaringan';
            const error = new Error(message);
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload;
    },

    async login(email, password) {
        const result = await this.request('/auth/login', {
            method: 'POST',
            body: { email, password },
            auth: false,
        });

        this.setToken(result.data.token);
        // store role and ID if provided by API
        if (result.data.user) {
            if (result.data.user.role) {
                localStorage.setItem('simpati_role', result.data.user.role);
            } else {
                localStorage.removeItem('simpati_role');
            }
            if (result.data.user.id) {
                localStorage.setItem('simpati_user_id', result.data.user.id);
            } else {
                localStorage.removeItem('simpati_user_id');
            }
        } else {
            localStorage.removeItem('simpati_role');
            localStorage.removeItem('simpati_user_id');
        }
        return result.data;
    },

    async logout() {
        try {
            await this.request('/auth/logout', { method: 'POST' });
        } catch (error) {
            // ignore logout failure and clear token anyway
        }

        this.clearToken();
        window.location.href = '/login';
    },

    async refreshToken() {
        const result = await this.request('/auth/refresh-token', { method: 'POST' });
        this.setToken(result.data.token);
        return result.data.token;
    },

    async me() {
        const result = await this.request('/auth/me');
        if (result.data) {
            if (result.data.role) {
                localStorage.setItem('simpati_role', result.data.role);
            }
            if (result.data.id) {
                localStorage.setItem('simpati_user_id', result.data.id);
            }
        }
        return result.data;
    },

    async fetchDashboard() {
        const result = await this.request('/dashboard');
        return result.data;
    },

    async fetchDebts() {
        const result = await this.request('/debts');
        return result.data;
    },

    async fetchDebt(id) {
        const result = await this.request(`/debts/${id}`);
        return result.data;
    },

    async fetchNotifications() {
        const result = await this.request('/notifications');
        return result.data;
    },

    async fetchStudents(page = 1, perPage = 10) {
        const result = await this.request(`/students?page=${page}&per_page=${perPage}`);
        return result;
    },

    async fetchProfile() {
        return this.me();
    },

    async fetchStudyPrograms() {
        const result = await this.request('/study-programs');
        return result.data;
    },

    async changePassword(oldPassword, newPassword, passwordConfirmation) {
        return this.request('/auth/change-password', {
            method: 'POST',
            body: {
                old_password: oldPassword,
                new_password: newPassword,
                new_password_confirmation: passwordConfirmation,
            },
        });
    },

    async handleUnauthorized(error) {
        if (error.status === 401 || error.status === 403) {
            this.clearToken();
            window.location.href = '/login';
        }

        throw error;
    },
};

window.addEventListener('DOMContentLoaded', () => {
    const nav = document.getElementById('appNav');
    const authButtons = document.querySelectorAll('[data-auth-only]');
    const guestButtons = document.querySelectorAll('[data-guest-only]');
        const roleAdminEls = document.querySelectorAll('[data-role="admin"]');
    const logoutButton = document.getElementById('logoutButton');

    if (nav) {
        nav.classList.toggle('hidden', !window.SIMPATI.isLoggedIn());
    }

    authButtons.forEach((el) => {
            el.classList.toggle('hidden', !window.SIMPATI.isLoggedIn());
    });

    guestButtons.forEach((el) => {
        el.classList.toggle('hidden', window.SIMPATI.isLoggedIn());
    });

        // role-based visibility
        const role = localStorage.getItem('simpati_role');
        const roleEls = document.querySelectorAll('[data-role]');
        roleEls.forEach((el) => {
            const requiredRole = el.getAttribute('data-role');
            if (requiredRole && requiredRole !== role) {
                el.classList.add('hidden');
            }
        });

    if (logoutButton) {
        logoutButton.addEventListener('click', async (event) => {
            event.preventDefault();
            await window.SIMPATI.logout();
        });
    }
});
