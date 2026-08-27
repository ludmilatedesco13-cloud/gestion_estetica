const currentRole = (localStorage.getItem('rol') || '').toLowerCase();
const currentUser = localStorage.getItem('usuario') || '';
const escapeUser = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
const userError = message => Swal.fire({ icon: 'error', title: 'No se pudo completar la operación', text: message, confirmButtonColor: '#dca397' });
const editIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
const deleteIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';

async function loadUsers() {
    const { data, error } = await supabaseClient.from('usuarios').select('id, usuario, rol').order('id', { ascending: false });
    if (error) throw error;
    document.getElementById('tabla-usuarios').innerHTML = data.length ? data.map(user => `
        <tr><td>${escapeUser(user.id)}</td><td><strong>${escapeUser(user.usuario)}</strong></td>
        <td><span class="role-badge">${user.rol === 'admin' ? 'Administrador' : 'Vendedor / Empleado'}</span></td>
        <td><div class="user-actions">${user.usuario === currentUser ? '<span style="color:#8a736d;">Cuenta actual</span>' : `<button type="button" class="user-action edit-user" data-id="${escapeUser(user.id)}" title="Editar usuario" aria-label="Editar usuario">${editIcon}</button><button type="button" class="user-action delete delete-user" data-id="${escapeUser(user.id)}" data-name="${escapeUser(user.usuario)}" title="Eliminar usuario" aria-label="Eliminar usuario">${deleteIcon}</button>`}</div></td></tr>`).join('') : '<tr><td colspan="4" style="text-align:center;">No hay usuarios registrados.</td></tr>';
}

async function createUser(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const username = document.getElementById('usuario').value.trim();
    const password = document.getElementById('password').value;
    const role = document.getElementById('rol').value;
    if (!username || password.length < 4) return userError('Completá el usuario y una contraseña de al menos 4 caracteres.');
    const existing = await supabaseClient.from('usuarios').select('id').eq('usuario', username).maybeSingle();
    if (existing.error) return userError(existing.error.message);
    if (existing.data) return userError(`El usuario "${username}" ya existe. Elegí otro nombre.`);
    const result = await supabaseClient.from('usuarios').insert({ usuario: username, password, rol: role });
    if (result.error) {
        const message = result.error.code === '23505'
            ? 'La base de datos rechazó el registro por un valor duplicado. Revisá las restricciones UNIQUE de usuarios y la secuencia del campo id.'
            : result.error.message;
        return userError(message);
    }
    form.reset();
    await Swal.fire({ icon: 'success', title: '¡Usuario creado! ✨', text: 'El usuario fue registrado correctamente.', confirmButtonColor: '#dca397' });
    await loadUsers();
}

async function editUser(id) {
    const { data, error } = await supabaseClient.from('usuarios').select('usuario, rol').eq('id', id).single();
    if (error) return userError(error.message);
    const result = await Swal.fire({ customClass: { popup: 'user-edit-popup' }, title: 'Editar usuario', html: `<div class="swal-user-form"><div class="swal-user-field"><label for="edit-username">Nombre de usuario</label><input id="edit-username" value="${escapeUser(data.usuario)}" autocomplete="username"></div><div class="swal-user-field"><label for="edit-password">Contraseña</label><input id="edit-password" type="password" placeholder="Dejar vacío para conservarla" autocomplete="new-password"></div><div class="swal-user-field"><label for="edit-role">Rol de acceso</label><select id="edit-role"><option value="vendedor">Vendedor / Empleado</option><option value="admin">Administrador</option></select></div></div>`, didOpen: () => { document.getElementById('edit-role').value = data.rol === 'admin' ? 'admin' : 'vendedor'; }, showCancelButton: true, confirmButtonText: 'Guardar cambios', cancelButtonText: 'Cancelar', confirmButtonColor: '#dca397', cancelButtonColor: '#7d6660', preConfirm: () => ({ usuario: document.getElementById('edit-username').value.trim(), password: document.getElementById('edit-password').value, rol: document.getElementById('edit-role').value }) });
    if (!result.isConfirmed || !result.value.usuario) return;
    const changes = { usuario: result.value.usuario, rol: result.value.rol };
    if (result.value.password) changes.password = result.value.password;
    const update = await supabaseClient.from('usuarios').update(changes).eq('id', id);
    if (update.error) return userError(update.error.code === '23505' ? 'Ese nombre de usuario ya está utilizado.' : update.error.message);
    await loadUsers();
    Swal.fire({ icon: 'success', title: 'Usuario actualizado', confirmButtonColor: '#dca397' });
}

async function deleteUser(id, name) {
    const confirmation = await Swal.fire({ title: '¿Eliminar usuario?', text: `¿Deseás eliminar "${name}"?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dca397', cancelButtonColor: '#7d6660', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' });
    if (!confirmation.isConfirmed) return;
    const { error } = await supabaseClient.from('usuarios').delete().eq('id', id);
    if (error) return userError(error.message);
    await loadUsers();
}

document.addEventListener('DOMContentLoaded', async () => {
    if (currentRole !== 'admin') { window.location.href = 'panel.html'; return; }
    document.getElementById('form-usuario').addEventListener('submit', createUser);
    document.getElementById('tabla-usuarios').addEventListener('click', event => { const button = event.target.closest('button'); if (!button) return; if (button.classList.contains('edit-user')) editUser(button.dataset.id); if (button.classList.contains('delete-user')) deleteUser(button.dataset.id, button.dataset.name); });
    try { await loadUsers(); } catch (error) { userError(error.message); }
});
