<script>
function ppmtEditorialPanel() {
    const todayStr = new Date().toISOString().slice(0, 10);
    const validTabs = ['publikasi', 'agenda', 'notulensi'];

    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        activeTab: 'publikasi',
        openMenuKey: null,
        toastMessage: '',
        toastType: 'success',
        announcements: [],
        newsArticles: [],
        agendas: [],
        meetingMinutes: [],
        showCreateAnnouncement: false,
        showCreateAgenda: false,
        showCreateMinute: false,
        savingAnnouncement: false,
        editingAnnouncement: null,
        savingNews: false,
        editingNews: null,
        savingAgenda: false,
        editingAgenda: null,
        savingMinute: false,
        editingMinute: null,
        announcementForm: {
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        editAnnouncementForm: {
            id: null,
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        editNewsForm: {
            id: null,
            title: '',
            category: 'Kegiatan Warga',
            layout: 'thumbnail',
            body: ''
        },
        agendaForm: {
            title: '',
            category: 'kegiatan',
            event_date: todayStr,
            time_label: '',
            location: '',
            notes: ''
        },
        editAgendaForm: {
            id: null,
            title: '',
            category: 'kegiatan',
            event_date: todayStr,
            time_label: '',
            location: '',
            notes: ''
        },
        minuteForm: {
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },
        editMinuteForm: {
            id: null,
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },

        toggleMenu(key) {
            this.openMenuKey = this.openMenuKey === key ? null : key;
        },

        closeMenu(key) {
            if (this.openMenuKey === key) {
                this.openMenuKey = null;
            }
        },

        closeAllModals() {
            this.showCreateAnnouncement = false;
            this.showCreateAgenda = false;
            this.showCreateMinute = false;
            this.editingAnnouncement = null;
            this.editingNews = null;
            this.editingAgenda = null;
            this.editingMinute = null;
        },

        switchTab(tab) {
            if (!validTabs.includes(tab)) return;
            this.openMenuKey = null;
            this.activeTab = tab;
            window.history.replaceState({}, '', '#' + tab);
        },

        openCreateAnnouncementModal() {
            this.openMenuKey = null;
            this.announcementForm = { title: '', category: 'info', body: '', is_pinned: false };
            this.showCreateAnnouncement = true;
        },

        openCreateAgendaModal() {
            this.openMenuKey = null;
            this.agendaForm = {
                title: '',
                category: 'kegiatan',
                event_date: todayStr,
                time_label: '',
                location: '',
                notes: ''
            };
            this.showCreateAgenda = true;
        },

        openCreateMinuteModal() {
            this.openMenuKey = null;
            this.minuteForm = {
                title: '',
                meeting_date: todayStr,
                location: '',
                category: 'musyawarah_warga',
                decisions: '',
                visibility: 'public_residents'
            };
            this.showCreateMinute = true;
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }

            const initialHash = (window.location.hash || '').replace('#', '').toLowerCase();
            if (validTabs.includes(initialHash)) {
                this.activeTab = initialHash;
            }

            await this.loadMe();
            const canManage = Boolean(this.user?.can_manage_announcements || this.user?.is_global_governor);
            if (!this.user?.has_admin_access || !canManage) {
                window.location.href = '/pengurus';
                return;
            }
            await Promise.all([
                this.loadAnnouncements(),
                this.loadNewsArticles(),
                this.loadAgendas(),
                this.loadMeetingMinutes()
            ]);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        formatNewsLayout(layout) {
            const labels = {
                'hero': 'Hero Utama',
                'thumbnail': 'Kartu Foto',
                'grid': 'Grid 2 Kolom',
                'text': 'Daftar Teks'
            };
            return labels[layout] || layout;
        },

        async loadMe() {
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        async loadAnnouncements() {
            const res = await fetch('/api/v1/announcements?all=1', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.announcements = json.data || [];
            }
        },

        async createAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch('/api/v1/announcements', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.announcementForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.showCreateAnnouncement = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.announcementForm = { title: '', category: 'info', body: '', is_pinned: false };
                    await this.loadAnnouncements();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menerbitkan pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        openEditAnnouncement(item) {
            this.editAnnouncementForm = {
                id: item.id,
                title: item.title,
                category: item.category_code,
                body: item.body,
                is_pinned: Boolean(item.is_pinned)
            };
            this.editingAnnouncement = item;
        },

        async saveEditedAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch(`/api/v1/announcements/${this.editAnnouncementForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editAnnouncementForm.title,
                        category: this.editAnnouncementForm.category,
                        body: this.editAnnouncementForm.body,
                        is_pinned: this.editAnnouncementForm.is_pinned
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingAnnouncement = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadAnnouncements();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        async toggleAnnouncementPin(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_pinned: !item.is_pinned })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_pinned ? 'Sematkan pengumuman dilepas.' : 'Pengumuman disematkan di urutan teratas.';
                await this.loadAnnouncements();
            }
        },

        async toggleAnnouncementPublish(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_published: !item.is_published })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_published ? 'Pengumuman diarsipkan dari beranda warga.' : 'Pengumuman kembali ditayangkan.';
                await this.loadAnnouncements();
            }
        },

        async deleteAnnouncement(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAnnouncements();
            }
        },

        async loadNewsArticles() {
            const res = await fetch('/api/v1/admin/news', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.newsArticles = json.data || [];
            }
        },

        openNewsEditModal(art) {
            this.editNewsForm = {
                id: art.id,
                title: art.title,
                category: art.category,
                layout: art.layout,
                body: art.body || ''
            };
            this.editingNews = art;
        },

        async saveEditedNews() {
            if (!this.editNewsForm.id) return;
            this.savingNews = true;
            try {
                const res = await fetch(`/api/v1/admin/news/${this.editNewsForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editNewsForm.title,
                        category: this.editNewsForm.category,
                        layout: this.editNewsForm.layout,
                        body: this.editNewsForm.body
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingNews = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadNewsArticles();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan perubahan berita.';
                }
            } finally {
                this.savingNews = false;
            }
        },

        async deleteNewsArticle(art) {
            if (!confirm(`Hapus berita "${art.title}"?`)) return;
            const res = await fetch(`/api/v1/admin/news/${art.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadNewsArticles();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menghapus berita.';
            }
        },

        async loadAgendas() {
            const res = await fetch('/api/v1/agendas?all=1', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.agendas = json.data || [];
            }
        },

        async createAgenda() {
            this.savingAgenda = true;
            try {
                const res = await fetch('/api/v1/agendas', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.agendaForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.showCreateAgenda = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.agendaForm = {
                        title: '',
                        category: 'kegiatan',
                        event_date: todayStr,
                        time_label: '',
                        location: '',
                        notes: ''
                    };
                    await this.loadAgendas();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan agenda.';
                }
            } finally {
                this.savingAgenda = false;
            }
        },

        openEditAgenda(ag) {
            this.editAgendaForm = {
                id: ag.id,
                title: ag.title,
                category: ag.category_code,
                event_date: ag.event_date,
                time_label: ag.time,
                location: ag.location,
                notes: ag.notes || ''
            };
            this.editingAgenda = ag;
        },

        async saveEditedAgenda() {
            this.savingAgenda = true;
            try {
                const res = await fetch(`/api/v1/agendas/${this.editAgendaForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editAgendaForm.title,
                        category: this.editAgendaForm.category,
                        event_date: this.editAgendaForm.event_date,
                        time_label: this.editAgendaForm.time_label,
                        location: this.editAgendaForm.location,
                        notes: this.editAgendaForm.notes || null
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingAgenda = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadAgendas();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui agenda.';
                }
            } finally {
                this.savingAgenda = false;
            }
        },

        async deleteAgenda(ag) {
            const res = await fetch(`/api/v1/agendas/${ag.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAgendas();
            }
        },

        async loadMeetingMinutes() {
            const res = await fetch('/api/v1/meeting-minutes', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.meetingMinutes = json.data || [];
            }
        },

        async createMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch('/api/v1/meeting-minutes', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.minuteForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.showCreateMinute = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.minuteForm = {
                        title: '',
                        meeting_date: todayStr,
                        location: '',
                        category: 'musyawarah_warga',
                        decisions: '',
                        visibility: 'public_residents'
                    };
                    await this.loadMeetingMinutes();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        openEditMinute(m) {
            this.editMinuteForm = {
                id: m.id,
                title: m.title,
                meeting_date: m.meeting_date,
                location: m.location || '',
                category: m.category_code,
                decisions: m.decisions,
                visibility: m.visibility
            };
            this.editingMinute = m;
        },

        async saveEditedMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch(`/api/v1/meeting-minutes/${this.editMinuteForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editMinuteForm.title,
                        meeting_date: this.editMinuteForm.meeting_date,
                        location: this.editMinuteForm.location || null,
                        category: this.editMinuteForm.category,
                        decisions: this.editMinuteForm.decisions,
                        visibility: this.editMinuteForm.visibility
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingMinute = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadMeetingMinutes();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        async deleteMinute(m) {
            const res = await fetch(`/api/v1/meeting-minutes/${m.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadMeetingMinutes();
            }
        }
    }
}
</script>