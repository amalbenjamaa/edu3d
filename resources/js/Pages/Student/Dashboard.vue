<template>
	<div class="student-root">
		<aside class="sidebar">
			<div class="brand">
				<div class="brand-icon">🎓</div>
				<div>
					<div class="brand-title">EDU3D</div>
					<div class="brand-subtitle">Espace étudiant</div>
				</div>
			</div>

			<nav class="nav">
				<button class="nav-item active">Vue globale</button>
				<button class="nav-item">Mes cours</button>
				<button class="nav-item">Mon profil</button>
			</nav>

			<Link :href="route('logout')" method="post" as="button" class="logout-btn">Déconnexion</Link>
		</aside>

		<main class="main">
			<section class="hero">
				<p class="eyebrow">Bienvenue</p>
				<h1>{{ auth?.user?.name || 'Étudiant' }}</h1>
				<p class="lead">Votre tableau de bord est prêt. Ajoutez maintenant les cours et suivis de progression.</p>
			</section>

			<section class="cards">
				<article class="card">
					<span class="card-label">Cours actifs</span>
					<strong>0</strong>
				</article>
				<article class="card">
					<span class="card-label">Progression</span>
					<strong>0%</strong>
				</article>
				<article class="card">
					<span class="card-label">Dernière activité</span>
					<strong>Aucune</strong>
				</article>
			</section>
		</main>
	</div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
	auth: Object,
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;700&display=swap');

:global(*) {
	box-sizing: border-box;
}

.student-root {
	min-height: 100vh;
	display: grid;
	grid-template-columns: 260px 1fr;
	background: #050b18;
	color: #fff;
	font-family: 'DM Sans', sans-serif;
}

.sidebar {
	padding: 1.5rem;
	border-right: 1px solid rgba(255, 255, 255, 0.06);
	background: rgba(255, 255, 255, 0.03);
	display: flex;
	flex-direction: column;
	gap: 2rem;
}

.brand {
	display: flex;
	align-items: center;
	gap: 0.9rem;
}

.brand-icon {
	width: 44px;
	height: 44px;
	border-radius: 14px;
	display: grid;
	place-items: center;
	background: rgba(0, 245, 212, 0.12);
	border: 1px solid rgba(0, 245, 212, 0.22);
}

.brand-title {
	font-family: 'Syne', sans-serif;
	font-weight: 800;
	letter-spacing: 0.04em;
}

.brand-subtitle {
	font-size: 0.8rem;
	color: rgba(255, 255, 255, 0.45);
}

.nav {
	display: grid;
	gap: 0.6rem;
}

.nav-item,
.logout-btn {
	width: 100%;
	border: 0;
	border-radius: 12px;
	padding: 0.85rem 1rem;
	text-align: left;
	font: inherit;
	color: inherit;
	background: rgba(255, 255, 255, 0.04);
}

.nav-item.active {
	background: rgba(0, 245, 212, 0.12);
	color: #00f5d4;
}

.logout-btn {
	margin-top: auto;
	background: rgba(239, 68, 68, 0.12);
	color: #fca5a5;
}

.main {
	padding: 2rem;
}

.hero {
	max-width: 680px;
	padding: 2rem;
	border-radius: 24px;
	background: linear-gradient(135deg, rgba(0, 245, 212, 0.08), rgba(99, 102, 241, 0.06));
	border: 1px solid rgba(255, 255, 255, 0.08);
}

.eyebrow {
	margin: 0 0 0.5rem;
	color: rgba(255, 255, 255, 0.55);
	text-transform: uppercase;
	letter-spacing: 0.12em;
	font-size: 0.75rem;
}

h1 {
	margin: 0;
	font-family: 'Syne', sans-serif;
	font-size: clamp(2rem, 4vw, 3.2rem);
}

.lead {
	margin: 0.8rem 0 0;
	max-width: 56ch;
	color: rgba(255, 255, 255, 0.62);
	line-height: 1.7;
}

.cards {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: 1rem;
	margin-top: 1.2rem;
}

.card {
	padding: 1.25rem;
	border-radius: 20px;
	background: rgba(255, 255, 255, 0.04);
	border: 1px solid rgba(255, 255, 255, 0.06);
}

.card-label {
	display: block;
	margin-bottom: 0.6rem;
	color: rgba(255, 255, 255, 0.45);
	font-size: 0.85rem;
}

.card strong {
	font-family: 'Syne', sans-serif;
	font-size: 1.6rem;
}

@media (max-width: 900px) {
	.student-root {
		grid-template-columns: 1fr;
	}

	.sidebar {
		border-right: 0;
		border-bottom: 1px solid rgba(255, 255, 255, 0.06);
	}

	.cards {
		grid-template-columns: 1fr;
	}
}
</style>
