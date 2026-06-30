import {
	Chart,
	LineController,
	LineElement,
	PointElement,
	LinearScale,
	CategoryScale,
	Filler,
	DoughnutController,
	ArcElement,
	Tooltip,
	Legend,
} from 'chart.js';

Chart.register(
	LineController,
	LineElement,
	PointElement,
	LinearScale,
	CategoryScale,
	Filler,
	DoughnutController,
	ArcElement,
	Tooltip,
	Legend,
);

const COLORS = {
	sky: '#38bdf8',
	rose: '#fb7185',
	amber: '#fbbf24',
	emerald: '#34d399',
	slate: '#64748b',
	gridline: 'rgba(148, 163, 184, 0.12)',
};

const readJson = (id) => {
	const node = document.getElementById(id);

	if (! node) {
		return null;
	}

	try {
		return JSON.parse(node.textContent);
	} catch (error) {
		console.warn(`Failed to parse dashboard data for #${id}`, error);

		return null;
	}
};

const renderTrendChart = () => {
	const canvas = document.getElementById('trend-chart');
	const points = readJson('dashboard-trend-data');

	if (! canvas || ! points) {
		return;
	}

	const chart = new Chart(canvas, {
		type: 'line',
		data: {
			labels: points.map((point) => point.label),
			datasets: [
				{
					label: 'Total messages',
					data: points.map((point) => point.total),
					borderColor: COLORS.sky,
					backgroundColor: 'rgba(56, 189, 248, 0.18)',
					fill: true,
					tension: 0.35,
					pointRadius: 0,
					pointHitRadius: 12,
				},
				{
					label: 'Failed messages',
					data: points.map((point) => point.failed),
					borderColor: COLORS.rose,
					backgroundColor: 'transparent',
					fill: false,
					tension: 0.35,
					pointRadius: 0,
					pointHitRadius: 12,
				},
			],
		},
		options: {
			responsive: true,
			interaction: { mode: 'index', intersect: false },
			scales: {
				x: { grid: { color: COLORS.gridline }, ticks: { color: '#94a3b8' } },
				y: { grid: { color: COLORS.gridline }, ticks: { color: '#94a3b8' }, beginAtZero: true },
			},
			plugins: {
				legend: { display: false },
				tooltip: {
					backgroundColor: '#0f172a',
					borderColor: 'rgba(255, 255, 255, 0.1)',
					borderWidth: 1,
					titleColor: '#f8fafc',
					bodyColor: '#cbd5f5',
				},
			},
			onClick: (_event, elements) => {
				const index = elements[0]?.index;
				const url = index === undefined ? null : points[index]?.reportUrl;

				if (url) {
					window.location.assign(url);
				}
			},
			onHover: (event, elements) => {
				event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
			},
		},
	});

	return chart;
};

const renderDispositionChart = () => {
	const canvas = document.getElementById('disposition-chart');
	const disposition = readJson('dashboard-disposition-data');

	if (! canvas || ! disposition) {
		return;
	}

	new Chart(canvas, {
		type: 'doughnut',
		data: {
			labels: ['None', 'Quarantine', 'Reject', 'Other'],
			datasets: [
				{
					data: [disposition.none, disposition.quarantine, disposition.reject, disposition.other],
					backgroundColor: [COLORS.sky, COLORS.amber, COLORS.rose, COLORS.slate],
					borderColor: '#0f172a',
					borderWidth: 2,
				},
			],
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: { display: false },
				tooltip: {
					backgroundColor: '#0f172a',
					borderColor: 'rgba(255, 255, 255, 0.1)',
					borderWidth: 1,
					titleColor: '#f8fafc',
					bodyColor: '#cbd5f5',
				},
			},
		},
	});
};

const POLL_INTERVAL_MS = 30000;

const startLiveStatsPolling = () => {
	const wrapper = document.getElementById('dashboard-live-stats');

	if (! wrapper) {
		return;
	}

	const targets = {
		total_accounts: document.getElementById('stat-total-accounts'),
		active_accounts: document.getElementById('stat-active-accounts'),
		total_reports: document.getElementById('stat-total-reports'),
		last_polled_at: document.getElementById('stat-last-poll'),
	};

	const intervalMs = Number(wrapper.dataset.pollInterval) || POLL_INTERVAL_MS;

	const tick = async () => {
		if (document.visibilityState === 'hidden') {
			return;
		}

		try {
			const response = await fetch(wrapper.dataset.url, {
				headers: { Accept: 'application/json' },
			});

			if (! response.ok) {
				return;
			}

			const data = await response.json();

			if (targets.total_accounts) {
				targets.total_accounts.textContent = data.total_accounts;
			}

			if (targets.active_accounts) {
				targets.active_accounts.textContent = data.active_accounts;
			}

			if (targets.total_reports) {
				targets.total_reports.textContent = data.total_reports;
			}

			if (targets.last_polled_at) {
				targets.last_polled_at.textContent = data.last_polled_at ?? 'Never polled';
			}
		} catch (error) {
			console.warn('Dashboard live-stats poll failed', error);
		}
	};

	window.setInterval(tick, intervalMs);
};

document.addEventListener('DOMContentLoaded', () => {
	window.__dashboardTrendChart = renderTrendChart();
	renderDispositionChart();
	startLiveStatsPolling();
});
