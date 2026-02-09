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

	const tipoColorMap = { ingreso: '#16a34a', egreso: '#dc2626', ajuste_pos: '#0ea5e9', ajuste_neg: '#ea580c', default: '#2563eb' };
	const tipoIconMap = { ingreso: 'arrow-down', egreso: 'arrow-up', ajuste_pos: 'plus', ajuste_neg: 'minus', default: 'exchange-alt' };

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
				items.forEach(item => renderNotificationCard(itemsContainer, item));
			}
			if (showPanel) openPanel();
	function renderNotificationCard(container, item) {
		const tipo = item.tipo || 'movimiento';
		const color = tipoColorMap[tipo] || tipoColorMap.default;
		const icon = tipoIconMap[tipo] || tipoIconMap.default;
		const headline = item.headline || `${formatTipoLabel(tipo, item.modalidad)} · ${item.producto || 'Producto'}`;
		const resumen = item.resumen || `${item.cantidad ?? '—'} unidades`;
		const subtext = item.subtext || buildLegacySubtext(item);
		const chips = Array.isArray(item.chips) && item.chips.length ? item.chips : buildLegacyChips(item);
		const actor = item.actor || null;

		const card = document.createElement('div');
		card.className = 'notif-card' + (item.leido ? '' : ' notif-card-unread');

		const iconBox = document.createElement('div');
		iconBox.className = 'notif-icon';
		iconBox.style.background = color;
		iconBox.innerHTML = `<i class="fa fa-${icon}"></i>`;
		card.appendChild(iconBox);

		const body = document.createElement('div');
		body.className = 'notif-body';
		card.appendChild(body);

		const headlineEl = document.createElement('div');
		headlineEl.className = 'notif-headline';
		headlineEl.textContent = headline;
		body.appendChild(headlineEl);

		const resumenEl = document.createElement('div');
		resumenEl.className = 'notif-resumen';
		resumenEl.textContent = resumen;
		body.appendChild(resumenEl);

		if (subtext) {
			const subtextEl = document.createElement('div');
			subtextEl.className = 'notif-subtext';
			subtextEl.textContent = subtext;
			body.appendChild(subtextEl);
		}

		if (actor) {
			const actorEl = document.createElement('div');
			actorEl.className = 'notif-actor';
			actorEl.textContent = `Registrado por ${actor}`;
			body.appendChild(actorEl);
		}

		if (chips.length) {
			const chipWrap = document.createElement('div');
			chipWrap.className = 'notif-chips';
			chips.forEach(chip => {
				const chipEl = document.createElement('span');
				chipEl.className = 'notif-chip';
				chipEl.textContent = chip;
				chipWrap.appendChild(chipEl);
			});
			body.appendChild(chipWrap);
		}

		const meta = document.createElement('div');
		meta.className = 'notif-meta';
		meta.textContent = item.fecha || '';
		body.appendChild(meta);

		container.appendChild(card);
	}

	function formatTipoLabel(tipo, modalidad) {
		switch (tipo) {
			case 'ingreso':
				return modalidad === 'ajuste' ? 'Ajuste positivo' : 'Entrada';
			case 'egreso':
				if (modalidad === 'distribucion') return 'Distribución';
				if (modalidad === 'consumo') return 'Consumo';
				return 'Salida';
			case 'ajuste_pos':
				return 'Ajuste positivo';
			case 'ajuste_neg':
				return 'Ajuste negativo';
			default:
				return 'Movimiento';
		}
	}

	function buildLegacySubtext(item) {
		const chunks = [];
		if (item.modalidad === 'distribucion' && item.destino) {
			chunks.push(`Destino: ${item.destino}`);
		}
		if (item.modalidad === 'consumo' && item.salida) {
			chunks.push(`Servicio: ${item.salida}`);
		}
		if (item.motivo) {
			chunks.push(item.motivo);
		}
		return chunks.join(' · ');
	}

	function buildLegacyChips(item) {
		const chips = [];
		if (item.modalidad === 'distribucion') chips.push('Distribución');
		if (item.modalidad === 'consumo') chips.push('Consumo');
		if (item.tipo && item.tipo.startsWith('ajuste')) chips.push('Ajuste');
		return chips;
	}

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
	@keyframes pulseBell { 0%{ transform:scale(1); } 50%{ transform:scale(1.15); } 100%{ transform:scale(1); } }
	.notif-card { display:flex;gap:.9rem;padding:.85rem;border-radius:1rem;border:1px solid #e2e8f0;background:#fff;margin-bottom:.6rem;box-shadow:0 6px 18px rgba(15,23,42,.08); }
	.notif-card-unread { border-color:#bfdbfe;background:#f0f7ff; box-shadow:0 8px 22px rgba(37,99,235,.15); }
	.notif-icon { width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem; }
	.notif-body { flex:1;display:flex;flex-direction:column;gap:.25rem;min-width:0; }
	.notif-headline { font-size:.78rem;font-weight:600;color:#0f172a; }
	.notif-resumen { font-size:.72rem;font-weight:600;color:#0f766e; }
	.notif-subtext { font-size:.68rem;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
	.notif-actor { font-size:.63rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em; }
	.notif-chips { display:flex;flex-wrap:wrap;gap:.35rem; }
	.notif-chip { font-size:.62rem;padding:.1rem .55rem;border-radius:999px;background:#eef2ff;color:#312e81;border:1px solid #c7d2fe;font-weight:600; }
	.notif-meta { font-size:.6rem;color:#94a3b8;margin-top:.15rem; }
	`;
	document.head.appendChild(st);
}
