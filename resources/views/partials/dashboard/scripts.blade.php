<script>
function ppmtFamilyRoom() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        pendingApprovals: [],
        generatedInviteUrl: '',
        selectedAnnouncement: null,
        copiedAnnouncement: false,
        showDuesModal: false,
        announcements: [],
        agendas: [],
        aspirations: [],
        treasuryReport: null,

        showContributeModal: false,
        submittingNews: false,
        newsPhotoFile: null,
        newsPhotoPreview: null,
        newsDraft: {
            category: 'Kegiatan Warga',
            layout: 'thumbnail',
            unit_id: '',
            title: '',
            body: ''
        },
        newsArticles: [],

        inviteForm: {
            unit_code: '',
            entity_type: 'resident',
            sub_role: 'family_member',
            member_valid_until: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (this.user?.is_verified) {
                await Promise.all([
                    this.loadAnnouncements(),
                    this.loadAgendas(),
                    this.loadNewsArticles(),
                    this.loadAspirations(),
                    this.loadPendingApprovals(),
                    this.loadTreasuryReport()
                ]);
            }
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        get canPublishNews() {
            return Boolean(this.user?.can_manage_announcements || this.user?.is_global_governor);
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        indoMonthShort(month) {
            const key = String(month || '').trim().toUpperCase();
            const map = {
                'JAN': 'JAN', 'JANUARY': 'JAN', 'JANUARI': 'JAN',
                'FEB': 'FEB', 'FEBRUARY': 'FEB', 'FEBRUARI': 'FEB',
                'MAR': 'MAR', 'MARCH': 'MAR', 'MARET': 'MAR',
                'APR': 'APR', 'APRIL': 'APR',
                'MAY': 'MEI', 'MEI': 'MEI',
                'JUN': 'JUN', 'JUNE': 'JUN', 'JUNI': 'JUN',
                'JUL': 'JUL', 'JULY': 'JUL', 'JULI': 'JUL',
                'AUG': 'AGU', 'AUGUST': 'AGU', 'AGUSTUS': 'AGU', 'AGT': 'AGU',
                'SEP': 'SEP', 'SEPTEMBER': 'SEP',
                'OCT': 'OKT', 'OCTOBER': 'OKT', 'OKTOBER': 'OKT',
                'NOV': 'NOV', 'NOVEMBER': 'NOV',
                'DEC': 'DES', 'DECEMBER': 'DES', 'DESEMBER': 'DES'
            };
            return map[key] || key;
        },

        subRoleLabel(role) {
            const labels = {
                'primary_owner': 'Pemilik Utama',
                'co_owner': 'Pemilik Bersama',
                'primary_resident': 'Penghuni Utama',
                'family_member': 'Anggota Keluarga',
                'co_resident': 'Penghuni Bersama',
                'tenant': 'Penyewa / Kontrak',
                'caretaker': 'Penjaga Rumah'
            };
            return labels[role] || role;
        },

        async loadMe() {
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;

                if (this.manageableUnits.length > 0 && !this.inviteForm.unit_code) {
                    this.inviteForm.unit_code = this.manageableUnits[0].unit.code;
                }
                if (this.approvedUnits.length > 0 && !this.newsDraft.unit_id) {
                    this.newsDraft.unit_id = this.approvedUnits[0].unit_id;
                }
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        get approvedUnits() {
            if (!this.user?.memberships) return [];
            return this.user.memberships.filter(m => m.is_approved && m.unit);
        },

        get manageableUnits() {
            if (!this.user?.memberships) return [];
            return this.user.memberships.filter(m =>
                m.is_approved && ['primary_owner', 'primary_resident'].includes(m.sub_role)
            );
        },

        get heroNews() {
            return this.newsArticles.find(n => n.layout === 'hero') || null;
        },

        get thumbnailNewsList() {
            return this.newsArticles.filter(n => n.layout === 'thumbnail');
        },

        get gridNewsList() {
            return this.newsArticles.filter(n => n.layout === 'grid');
        },

        get textOnlyNewsList() {
            return this.newsArticles.filter(n => n.layout === 'text');
        },

        newsUrl(item) {
            if (!item) return '#';
            return `/berita/${item.date_path}/${item.slug}`;
        },

        handleNewsPhotoChange(event) {
            const file = event.target.files?.[0];
            if (!file) {
                this.clearNewsPhoto();
                return;
            }
            if (file.size > 3 * 1024 * 1024) {
                this.toastType = 'error';
                this.toastMessage = 'Ukuran foto maksimal 3 MB.';
                this.clearNewsPhoto();
                return;
            }
            this.newsPhotoFile = file;
            this.newsPhotoPreview = URL.createObjectURL(file);
        },

        clearNewsPhoto() {
            if (this.newsPhotoPreview) {
                URL.revokeObjectURL(this.newsPhotoPreview);
            }
            this.newsPhotoFile = null;
            this.newsPhotoPreview = null;
            if (this.$refs.newsPhotoInput) {
                this.$refs.newsPhotoInput.value = '';
            }
        },

        async loadNewsArticles() {
            const res = await fetch('/api/v1/news', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.newsArticles = json.data || [];
            }
        },

        async submitNewsContribution() {
            if (!this.newsDraft.title.trim() || !this.newsDraft.body.trim()) return;
            this.submittingNews = true;

            try {
                const formData = new FormData();
                formData.append('title', this.newsDraft.title.trim());
                formData.append('category', this.newsDraft.category);
                formData.append('layout', this.newsDraft.layout);
                formData.append('body', this.newsDraft.body.trim());
                if (this.newsDraft.unit_id) {
                    formData.append('unit_id', this.newsDraft.unit_id);
                }
                if (this.newsPhotoFile) {
                    formData.append('photo', this.newsPhotoFile);
                }

                const res = await fetch('/api/v1/news', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const json = await res.json();

                if (res.ok) {
                    this.newsDraft.title = '';
                    this.newsDraft.body = '';
                    this.newsDraft.layout = 'thumbnail';
                    this.clearNewsPhoto();
                    this.showContributeModal = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadNewsArticles();
                } else {
                    const firstError = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                    this.toastType = 'error';
                    this.toastMessage = firstError || json.message || 'Gagal menerbitkan kabar warga.';
                }
            } catch (e) {
                this.toastType = 'error';
                this.toastMessage = 'Terjadi gangguan koneksi saat menerbitkan kabar.';
            } finally {
                this.submittingNews = false;
            }
        },

        get unsignedOpenAspirations() {
            return this.aspirations.filter(a => a.status === 'open' && !a.is_signed_by_me);
        },

        async loadTreasuryReport() {
            const res = await fetch('/api/v1/treasury/public-report', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.treasuryReport = json.data || null;
            }
        },

        async loadAnnouncements() {
            const res = await fetch('/api/v1/announcements', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.announcements = json.data || [];
            }
        },

        async loadAgendas() {
            const res = await fetch('/api/v1/agendas', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.agendas = json.data || [];
            }
        },

        async loadAspirations() {
            const res = await fetch('/api/v1/aspirations', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.aspirations = json.data || [];
            }
        },

        async loadPendingApprovals() {
            const res = await fetch('/api/v1/verifications/pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingApprovals = json.data || [];
            }
        },

        async approveResident(item) {
            const res = await fetch('/api/v1/verifications/approve', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    target_user_id: item.target_user_id,
                    unit_code: item.unit_code
                })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadPendingApprovals();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mengesahkan warga.';
            }
        },

        async generateInvite() {
            const res = await fetch(`/api/v1/units/${this.inviteForm.unit_code}/invites`, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.inviteForm)
            });
            const json = await res.json();
            if (res.ok) {
                const token = json.data.invite_token;
                const unit = json.data.unit_code;
                this.generatedInviteUrl = `${window.location.origin}/?unit=${unit}&invite=${token}`;
                this.toastType = 'success';
                this.toastMessage = json.message;
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal membuat tautan undangan.';
            }
        },

        copyInviteUrl() {
            navigator.clipboard.writeText(this.generatedInviteUrl);
            this.toastType = 'success';
            this.toastMessage = 'Tautan undangan disalin! Siap ditempel di WhatsApp keluarga/penyewa.';
        },

        async copyAnnouncementText(item) {
            if (!item) return;
            const text = `*${item.title}*\n(${item.category} • ${item.date})\n\n${item.body}\n\n— Info Warga PPMT Digital`;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                }

                this.copiedAnnouncement = true;
                setTimeout(() => { this.copiedAnnouncement = false; }, 2000);
            } catch (e) {
                this.toastType = 'error';
                this.toastMessage = 'Gagal menyalin teks pengumuman.';
            }
        }
    }
}
</script>