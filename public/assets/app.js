(() => {
    'use strict';

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    class ApiError extends Error {
        constructor(payload) {
            super(payload?.error || 'Có lỗi xảy ra, vui lòng thử lại.');
            this.payload = payload || {};
        }
    }

    async function api(path, options = {}) {
        const method = options.method || 'GET';
        const headers = { Accept: 'application/json' };

        if (options.body !== undefined) {
            headers['Content-Type'] = 'application/json';
        }
        if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && csrfMeta) {
            headers['X-CSRF-Token'] = csrfMeta.content;
        }

        const response = await fetch(`api/${path}`, {
            method,
            headers,
            body: options.body === undefined ? undefined : JSON.stringify(options.body),
        });
        const payload = response.status === 204 ? null : await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new ApiError(payload);
        }

        return payload;
    }

    function element(tag, attributes = {}, text = '') {
        const node = document.createElement(tag);

        Object.entries(attributes).forEach(([name, value]) => {
            if (name === 'className') {
                node.className = value;
            } else {
                node.setAttribute(name, value);
            }
        });

        if (text !== '') {
            node.textContent = String(text);
        }

        return node;
    }

    function formValues(form) {
        const values = Object.fromEntries(new FormData(form).entries());
        return values;
    }

    function populateForm(form, data) {
        Object.entries(data).forEach(([name, value]) => {
            const field = form.elements.namedItem(name);
            if (!field) {
                return;
            }

            if (field instanceof RadioNodeList) {
                [...field].forEach((radio) => {
                    radio.checked = radio.value === String(value ?? '');
                });
            } else {
                field.value = value ?? '';
            }
        });
    }

    function clearErrors(form) {
        form.querySelectorAll('.api-field-error').forEach((node) => node.remove());
        const summary = form.querySelector('[data-form-error]');
        if (summary) {
            summary.textContent = '';
            summary.hidden = true;
        }
    }

    function showErrors(form, error) {
        clearErrors(form);
        const errors = error.payload?.errors || {};

        Object.entries(errors).forEach(([name, message]) => {
            const field = form.elements.namedItem(name);
            const target = field instanceof RadioNodeList ? field[field.length - 1] : field;
            if (!target) {
                return;
            }

            const errorNode = element('span', { className: 'field-error api-field-error' }, message);
            target.insertAdjacentElement('afterend', errorNode);
        });

        const summary = form.querySelector('[data-form-error]');
        if (summary) {
            summary.textContent = error.message;
            summary.hidden = false;
        }
    }

    function queryString(values) {
        const params = new URLSearchParams();

        Object.entries(values).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                params.set(key, String(value));
            }
        });

        return params.toString();
    }

    function appendActionLink(container, label, href, className) {
        container.append(element('a', { className: `btn ${className}`, href }, label));
    }

    function appendActionButton(container, label, className, onClick) {
        const button = element('button', { className: `btn ${className}`, type: 'button' }, label);
        button.addEventListener('click', () => {
            Promise.resolve(onClick()).catch((error) => window.alert(error.message));
        });
        container.append(button);
    }

    document.querySelectorAll('[data-api-logout]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            try {
                await api('session', { method: 'DELETE' });
                window.location.href = 'login.php';
            } catch (error) {
                window.alert(error.message);
            }
        });
    });

    const loginForm = document.querySelector('[data-login-form]');
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors(loginForm);

            try {
                await api('session', { method: 'POST', body: formValues(loginForm) });
                window.location.href = 'index.php';
            } catch (error) {
                showErrors(loginForm, error);
            }
        });
    }

    const studentList = document.querySelector('[data-students-list]');
    if (studentList) {
        const form = document.querySelector('[data-students-filter]');
        const tbody = studentList.querySelector('tbody');
        const pagination = document.querySelector('[data-students-pagination]');

        const fillOptions = (select, options, prefix) => {
            const selected = select.value;
            select.replaceChildren(element('option', { value: '' }, prefix));
            options.forEach((value) => select.append(element('option', { value }, value)));
            select.value = selected;
        };

        const load = async () => {
            const values = formValues(form);
            values.page = new URLSearchParams(window.location.search).get('page') || '1';
            const payload = await api(`students?${queryString(values)}`);
            const students = payload.data;
            const meta = payload.meta;

            tbody.replaceChildren();
            if (students.length === 0) {
                const row = element('tr');
                row.append(element('td', { colspan: '8', className: 'empty-state' }, 'Chưa có dữ liệu sinh viên.'));
                tbody.append(row);
            }

            students.forEach((student) => {
                const row = element('tr');
                ['id', 'student_code', 'full_name', 'email', 'phone', 'major', 'year'].forEach((key) => {
                    row.append(element('td', {}, student[key] ?? ''));
                });
                const actions = element('td', { className: 'actions' });
                appendActionLink(actions, 'Chi tiết', `student_detail.php?id=${student.id}`, 'btn-primary');
                if (studentList.dataset.admin === '1') {
                    appendActionLink(actions, 'Sửa', `student_edit.php?id=${student.id}`, 'btn-warning');
                    appendActionButton(actions, 'Xóa', 'btn-danger', async () => {
                        if (!window.confirm('Bạn có chắc chắn muốn xóa sinh viên này không?')) {
                            return;
                        }
                        await api(`students/${student.id}`, { method: 'DELETE' });
                        await load();
                    });
                }
                row.append(actions);
                tbody.append(row);
            });

            fillOptions(form.elements.major, meta.filter_options.majors, 'Tất cả ngành học');
            fillOptions(form.elements.year, meta.filter_options.years, 'Tất cả năm học');
            pagination.replaceChildren();
            for (let page = 1; page <= meta.total_pages; page += 1) {
                const valuesForPage = { ...formValues(form), page };
                const link = element('a', {
                    className: page === meta.page ? 'active' : '',
                    href: `students.php?${queryString(valuesForPage)}`,
                }, page);
                pagination.append(link);
            }
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            window.location.href = `students.php?${queryString(formValues(form))}`;
        });

        load().catch((error) => window.alert(error.message));
    }

    const studentForm = document.querySelector('[data-student-form]');
    if (studentForm) {
        const studentId = studentForm.dataset.studentId;

        if (studentId) {
            api(`students/${studentId}`)
                .then((payload) => populateForm(studentForm, payload.data))
                .catch((error) => window.alert(error.message));
        }

        studentForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors(studentForm);

            try {
                await api(studentId ? `students/${studentId}` : 'students', {
                    method: studentId ? 'PATCH' : 'POST',
                    body: formValues(studentForm),
                });
                window.location.href = 'students.php';
            } catch (error) {
                showErrors(studentForm, error);
            }
        });
    }

    const studentDetail = document.querySelector('[data-student-detail]');
    if (studentDetail) {
        const labels = {
            id: 'ID',
            student_code: 'Mã sinh viên',
            full_name: 'Họ tên',
            email: 'Email',
            phone: 'Số điện thoại',
            major: 'Ngành học',
            year: 'Năm học',
            created_at: 'Ngày tạo',
            updated_at: 'Ngày cập nhật',
        };

        api(`students/${studentDetail.dataset.studentId}`)
            .then((payload) => {
                const student = payload.data;
                document.querySelector('[data-student-title]').textContent = student.full_name;
                studentDetail.replaceChildren();
                Object.entries(student).forEach(([key, value]) => {
                    const card = element('div', { className: 'card' });
                    card.append(element('span', { className: 'meta-label' }, labels[key] || key));
                    card.append(element('strong', {}, value ?? ''));
                    studentDetail.append(card);
                });
            })
            .catch((error) => window.alert(error.message));
    }

    document.querySelectorAll('[data-delete-student]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!window.confirm('Bạn có chắc chắn muốn xóa sinh viên này không?')) {
                return;
            }

            try {
                await api(`students/${button.dataset.deleteStudent}`, { method: 'DELETE' });
                window.location.href = 'students.php';
            } catch (error) {
                window.alert(error.message);
            }
        });
    });

    const userList = document.querySelector('[data-users-list]');
    if (userList) {
        const tbody = userList.querySelector('tbody');

        const load = async () => {
            const payload = await api('users');
            tbody.replaceChildren();

            payload.data.forEach((user) => {
                const row = element('tr');
                ['id', 'username', 'full_name', 'email', 'role', 'status'].forEach((key) => {
                    row.append(element('td', {}, user[key] ?? ''));
                });
                const actions = element('td', { className: 'actions' });
                appendActionLink(actions, 'Sửa', `user_edit.php?id=${user.id}`, 'btn-warning');

                if (String(user.id) !== userList.dataset.currentUserId) {
                    const nextStatus = user.status === 'active' ? 'locked' : 'active';
                    appendActionButton(actions, nextStatus === 'locked' ? 'Khóa' : 'Mở khóa', nextStatus === 'locked' ? 'btn-danger' : 'btn-primary', async () => {
                        await api(`users/${user.id}`, { method: 'PATCH', body: { status: nextStatus } });
                        await load();
                    });
                    appendActionButton(actions, 'Xóa', 'btn-danger', async () => {
                        if (!window.confirm('Bạn có chắc chắn muốn xóa tài khoản này không?')) {
                            return;
                        }
                        await api(`users/${user.id}`, { method: 'DELETE' });
                        await load();
                    });
                }

                row.append(actions);
                tbody.append(row);
            });
        };

        load().catch((error) => window.alert(error.message));
    }

    const userForm = document.querySelector('[data-user-form]');
    if (userForm) {
        const userId = userForm.dataset.userId;

        if (userId) {
            api(`users/${userId}`)
                .then((payload) => populateForm(userForm, payload.data))
                .catch((error) => window.alert(error.message));
        }

        userForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors(userForm);

            try {
                await api(userId ? `users/${userId}` : 'users', {
                    method: userId ? 'PATCH' : 'POST',
                    body: formValues(userForm),
                });
                window.location.href = 'users.php';
            } catch (error) {
                showErrors(userForm, error);
            }
        });
    }

    const profilePage = document.querySelector('[data-profile-page]');
    if (profilePage) {
        const form = profilePage.querySelector('[data-profile-form]');

        api('profile')
            .then((payload) => {
                populateForm(form, payload.data);
                profilePage.querySelectorAll('[data-profile-field]').forEach((node) => {
                    node.textContent = payload.data[node.dataset.profileField] ?? '';
                });
            })
            .catch((error) => window.alert(error.message));

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors(form);

            try {
                await api('profile', { method: 'PATCH', body: formValues(form) });
                window.location.reload();
            } catch (error) {
                showErrors(form, error);
            }
        });
    }

    const passwordForm = document.querySelector('[data-password-form]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearErrors(passwordForm);

            try {
                await api('profile/password', { method: 'PUT', body: formValues(passwordForm) });
                window.location.href = 'profile.php';
            } catch (error) {
                showErrors(passwordForm, error);
            }
        });
    }

    const dashboard = document.querySelector('[data-dashboard]');
    if (dashboard) {
        api('dashboard')
            .then((payload) => {
                const data = payload.data;
                dashboard.querySelectorAll('[data-dashboard-value]').forEach((node) => {
                    node.textContent = data[node.dataset.dashboardValue] ?? '0';
                });
                const majors = dashboard.querySelector('[data-dashboard-majors]');
                majors.replaceChildren();
                data.major_stats.forEach((row) => {
                    const item = element('div');
                    item.append(element('span', {}, row.major));
                    item.append(element('strong', {}, row.total));
                    majors.append(item);
                });
                const tbody = dashboard.querySelector('[data-dashboard-students]');
                tbody.replaceChildren();
                data.recent_students.forEach((student) => {
                    const row = element('tr');
                    ['student_code', 'full_name', 'email', 'major', 'year'].forEach((key) => {
                        row.append(element('td', {}, student[key] ?? ''));
                    });
                    tbody.append(row);
                });
            })
            .catch((error) => window.alert(error.message));
    }

    const logTable = document.querySelector('[data-logs-list]');
    if (logTable) {
        const form = document.querySelector('[data-logs-filter]');
        const tbody = logTable.querySelector('tbody');

        const load = async () => {
            const payload = await api(`logs?${queryString(formValues(form))}`);
            tbody.replaceChildren();
            if (payload.data.length === 0) {
                const row = element('tr');
                row.append(element('td', { colspan: '2', className: 'empty-state' }, 'Chưa có log phù hợp.'));
                tbody.append(row);
            }
            payload.data.forEach((line, index) => {
                const row = element('tr');
                row.append(element('td', {}, index + 1));
                const content = element('td');
                content.append(element('code', {}, line));
                row.append(content);
                tbody.append(row);
            });
            window.dispatchEvent(new Event('app:logs-updated'));
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            load().catch((error) => window.alert(error.message));
        });
        load().catch((error) => window.alert(error.message));
    }
})();
