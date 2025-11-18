import './bootstrap';
// Fuentes locales (sin internet)
import '@fontsource/montserrat/400.css';
import '@fontsource/montserrat/800.css';
import '@fontsource/roboto/400.css';
import '@fontsource/roboto/700.css';
// CSS de Bootstrap y Font Awesome locales
import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
// JS de Bootstrap local
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// ================= NOTIFICACIONES DE MOVIMIENTOS (campana) =================
document.addEventListener('DOMContentLoaded', () => {
	const bell = document.getElementById('notifBell');
	if (!bell) return; // Solo en layout con campana
	const panel = document.getElementById('notifPanel');
	const itemsContainer = document.getElementById('notifItems');
	const countSpan = document.getElementById('notifCount');
	const emptyDiv = document.getElementById('notifEmpty');
	const markAllBtn = document.getElementById('notifMarkAll');
	let panelOpen = false;

		async function fetchNotifs(showPanel = false) {
		try {
			const url = '/notificaciones/movimientos' + (showPanel ? '?panel=1' : '');
			const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
			if (!r.ok) {
				console.warn('Notificaciones: respuesta no OK', r.status);
				return;
			}
			let data;
			try {
				data = await r.json();
			} catch(parseErr) {
				console.error('Error parseando JSON notificaciones', parseErr);
				showToast('Error leyendo notificaciones', 'error');
				return;
			}
			if (!data || typeof data !== 'object') {
				console.warn('Formato inesperado notificaciones');
				return;
			}
			const unread = data.unread || 0;
			if (unread > 0) {
				countSpan.style.display = 'inline';
				countSpan.textContent = unread > 99 ? '99+' : unread;
				bell.classList.add('pulse-bell');
			} else {
				countSpan.style.display = 'none';
				bell.classList.remove('pulse-bell');
			}
			itemsContainer.innerHTML = '';
			const items = data.items || [];
			if (items.length === 0) {
				emptyDiv.style.display = 'block';
			} else {
				emptyDiv.style.display = 'none';
				items.forEach(m => {
					const div = document.createElement('div');
					div.style.cssText = 'display:flex;align-items:flex-start;gap:10px;padding:7px 8px;border-radius:10px;margin-bottom:4px;background:' + (m.leido ? '#f5f7fa' : '#eaf4ff') + ';border:1px solid ' + (m.leido ? '#dde4ea' : '#c2ddf5') + ';';
					const colorMap = { ingreso: '#27ae60', egreso: '#e74c3c', ajuste_pos: '#2ecc71', ajuste_neg: '#e67e22' };
					const iconMap = { ingreso: 'arrow-down', egreso: 'arrow-up', ajuste_pos: 'plus', ajuste_neg: 'minus' };
					const icon = iconMap[m.tipo] || 'exchange-alt';
					const color = colorMap[m.tipo] || '#4093c7';
					div.innerHTML = `<div style="width:24px;height:24px;border-radius:7px;background:${color};display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;"><i class="fa fa-${icon}"></i></div>
						<div style="flex:1;display:flex;flex-direction:column;gap:2px;font-size:.72rem;line-height:1.05;">
							<div style="font-weight:600;color:#2c3e50;">${m.tipo.replace('_',' ')} · <span style="color:${color}">${m.cantidad}</span> uds</div>
							<div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#34495e;">${m.producto}</div>
							<div style="color:#607d8b;font-size:.65rem;">${m.fecha} · ${m.motivo || ''}</div>
						</div>`;
					itemsContainer.appendChild(div);
				});
			}
			if (showPanel) openPanel();
		} catch (e) {
			console.error('Error notificaciones', e);
		}
	}

	function openPanel() {
		panel.style.display = 'block';
		panelOpen = true;
		document.addEventListener('click', outsideClose);
	}
	function closePanel() {
		panel.style.display = 'none';
		panelOpen = false;
		document.removeEventListener('click', outsideClose);
	}
	function outsideClose(ev) {
		if (!panel.contains(ev.target) && ev.target !== bell) {
			closePanel();
		}
	}

	bell.addEventListener('click', (e) => {
		e.stopPropagation();
		if (panelOpen) { closePanel(); return; }
		fetchNotifs(true); // marca apertura del panel -> ?panel=1
	});

	if (markAllBtn) {
		markAllBtn.addEventListener('click', async () => {
			try {
				const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
				const r = await fetch('/notificaciones/movimientos/leer', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
				});
				if (r.ok) {
					fetchNotifs();
				}
			} catch (e) {
				console.error('Error marcar leídas', e);
			}
		});
	}

	// Poll cada 60s para actualizar contador silencioso
	setInterval(() => fetchNotifs(false), 60000);
	// Carga inicial del contador
	fetchNotifs(false);
});

// Animación campana (CSS inline injection si no existe)
const bellStyleId = 'notif-bell-style';
if (!document.getElementById(bellStyleId)) {
	const st = document.createElement('style');
	st.id = bellStyleId;
	st.textContent = `.pulse-bell { animation: pulseBell 1.2s ease-in-out infinite; }
	@keyframes pulseBell { 0%{ transform:scale(1); } 50%{ transform:scale(1.15); } 100%{ transform:scale(1); } }`;
	document.head.appendChild(st);
}
