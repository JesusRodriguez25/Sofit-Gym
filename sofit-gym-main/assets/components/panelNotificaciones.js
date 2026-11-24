import Alpine from "alpinejs";
import dayjs from "dayjs";
import snarkdown from "snarkdown";
import { fetchApi } from "@/js/api.js";

const ENDPOINT = "notificaciones";

function parseLeido(valor) {
	return valor === true || valor === 1 || valor === "1";
}

Alpine.data("panelNotificaciones", () => ({
	notificaciones: [],
	unreadCount: 0,

	async init() {
		await this.refresh();
		setInterval(async () => await this.refresh(), 60000);
	},

	async refresh() {
		const data = await fetchApi({
			page: ENDPOINT,
			action: "query",
		});

		const lista = Array.isArray(data) ? data : [];
		this.notificaciones = lista.map((notif) => ({
			...notif,
			leido: parseLeido(notif.leido),
			contenido: snarkdown(notif.contenido || ""),
			expanded: false,
		}));

		this.unreadCount = this.notificaciones.filter((n) => !n.leido).length;
		await this.notifyUpdate();
	},

	async notifyUpdate() {
		this.$dispatch("update-notifications", { unreaded: this.unreadCount });
	},

	async marcarLeida(id, leido = true) {
		if (!id) return;

		const estadoLeido = Boolean(leido);

		await fetchApi({
			page: ENDPOINT,
			action: "leido",
			id: id,
			leido: estadoLeido,
		});

		const notif = this.notificaciones.find((n) => n.id_notificacion === id);
		if (notif) {
			notif.leido = estadoLeido;
			this.unreadCount = this.notificaciones.filter((n) => !n.leido).length;
			await this.notifyUpdate();
		}

		await this.refresh();
	},

	async marcarTodasLeidas() {
		await fetchApi({
			page: ENDPOINT,
			action: "leerTodas",
		});

		this.notificaciones.forEach((n) => {
			n.leido = true;
		});
		this.unreadCount = 0;
		await this.notifyUpdate();
		await this.refresh();
	},

	humanDate(date) {
		if (!date) return "";
		const formatted = dayjs(date);
		return formatted.isValid() ? formatted.format("HH:mm DD/MM/YYYY") : String(date);
	},
}));