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

	// ====== Gestión de ciclo de vida del polling ======
	let pollTimer = null;
	let baseIntervalMs = 60000; // 60s por defecto
	let maxIntervalMs = 300000; // 5 min máximo cuando no hay cambios
	let currentIntervalMs = baseIntervalMs;
	let consecutiveNoUpdates = 0;
	let isPaused = false; // por visibilidad o inactividad

	// Detección de inactividad (idle): pausa después de 5 min sin interacción
	const idleThresholdMs = 5 * 60 * 1000;
	let lastActivityTs = Date.now();
	function markActivity(){ lastActivityTs = Date.now(); if(isPaused){ resumePolling(); } }
	['mousemove','keydown','click','scroll','touchstart'].forEach(evt=>document.addEventListener(evt, markActivity, {passive:true}));

	function checkIdle(){
		const idle = (Date.now() - lastActivityTs) >= idleThresholdMs;
		if (idle && !isPaused){ pausePolling('idle'); }
		else if (!idle && isPaused){ resumePolling(); }
	}
	setInterval(checkIdle, 15000); // comprobar cada 15s

	// Pausar cuando la pestaña esté oculta; reanudar al volver visible
	document.addEventListener('visibilitychange', () => {
		if (document.hidden) pausePolling('hidden'); else resumePolling();
	});

	function pausePolling(reason='manual'){
		isPaused = true;
		if (pollTimer){ clearTimeout(pollTimer); pollTimer = null; }
		// Opcional: feedback en consola
		console.debug('[Polling] Pausado por:', reason);
	}
	function resumePolling(){
		if (!isPaused) return;
		isPaused = false;
		scheduleNext(1000); // reanudar rápido con un segundo
		console.debug('[Polling] Reanudado');
	}
	function scheduleNext(delayMs = currentIntervalMs){
		if (pollTimer){ clearTimeout(pollTimer); }
		pollTimer = setTimeout(() => { if(!isPaused) fetchNotifs(false, true); }, delayMs);
	}

		async function fetchNotifs(showPanel = false, fromTimer = false) {
		try {
			const url = '/notificaciones/movimientos' + (showPanel ? '?panel=1' : '');
			const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
			if (!r.ok) {
				console.warn('Notificaciones: respuesta no OK', r.status);
				// Si el servidor indica no autorizado o CSRF expirado, detener el ciclo
				if (r.status === 401 || r.status === 403 || r.status === 419){ pausePolling('unauthorized'); }
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
				// Ajuste de backoff: si hay novedades, volver a intervalo base
				consecutiveNoUpdates = 0;
				currentIntervalMs = baseIntervalMs;
			} else {
				countSpan.style.display = 'none';
				bell.classList.remove('pulse-bell');
				// Incrementar intervalo gradualmente hasta el máximo
				consecutiveNoUpdates++;
				const step = Math.min(consecutiveNoUpdates, 5); // limitar pasos
				currentIntervalMs = Math.min(baseIntervalMs + step * 30000, maxIntervalMs); // +30s por paso
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

			// Si vino del temporizador, programar la siguiente ejecución con el backoff actual
			if (fromTimer && !isPaused){ scheduleNext(currentIntervalMs); }
		} catch (e) {
			console.error('Error notificaciones', e);
			// En caso de error de red, aumentar ligeramente el intervalo para evitar ciclados
			currentIntervalMs = Math.min(currentIntervalMs + 30000, maxIntervalMs);
			if (!isPaused){ scheduleNext(currentIntervalMs); }
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

	// Detener polling al hacer logout (ambos formularios en layout)
	document.querySelectorAll('form[action$="/logout"]').forEach(f => {
		f.addEventListener('submit', () => pausePolling('logout'));
	});

	// Arranque: primera carga y programación siguiente
	fetchNotifs(false);
	scheduleNext(currentIntervalMs);
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
