<template>
  <div class="auth-root">
    <!-- Animated background canvas -->
    <canvas ref="canvas" class="bg-canvas"></canvas>

    <!-- Floating particles -->
    <div class="particles">
      <span v-for="i in 18" :key="i" :class="`p p${i}`"></span>
    </div>

    <div class="auth-layout">
      <!-- LEFT PANEL -->
      <div class="left-panel">
        <div class="brand">
          <div class="logo-wrap">
            <svg width="38" height="38" viewBox="0 0 38 38" fill="none">
              <polygon points="19,2 36,32 2,32" fill="none" stroke="#00f5d4" stroke-width="2.2"/>
              <polygon points="19,10 30,28 8,28" fill="#00f5d4" opacity="0.18"/>
              <circle cx="19" cy="19" r="4" fill="#00f5d4"/>
            </svg>
          </div>
          <span class="brand-name">EDU<em>3D</em></span>
        </div>

        <div class="left-content">
          <h1 class="hero-title">
            <span class="line">Apprenez</span>
            <span class="line accent">en 3D.</span>
            <span class="line">Vivez le</span>
            <span class="line accent2">savoir.</span>
          </h1>
          <p class="hero-sub">Une plateforme immersive où chaque cours devient une expérience spatiale unique.</p>

          <div class="features">
            <div class="feat" v-for="f in features" :key="f.label">
              <div class="feat-icon">{{ f.icon }}</div>
              <div>
                <div class="feat-title">{{ f.label }}</div>
                <div class="feat-desc">{{ f.desc }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="left-footer">
          <div class="stats">
            <div class="stat" v-for="s in stats" :key="s.val">
              <span class="stat-val">{{ s.val }}</span>
              <span class="stat-label">{{ s.label }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT PANEL -->
      <div class="right-panel">
        <div class="card">
          <div class="form-header">
            <h2>Bon retour 👋</h2>
            <p>Connectez-vous à votre espace immersif</p>
          </div>

          <form class="form" @submit.prevent="handleLogin">
            <div class="field" :class="{ focused: focus.email, error: form.errors.email }">
              <label>Email</label>
              <div class="input-wrap">
                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
                <input type="email" v-model="form.email" placeholder="vous@exemple.com"
                  @focus="focus.email = true" @blur="focus.email = false" autocomplete="email"/>
              </div>
              <span class="err-msg" v-if="form.errors.email">{{ form.errors.email }}</span>
            </div>

            <div class="field" :class="{ focused: focus.password, error: form.errors.password }">
              <label>Mot de passe</label>
              <div class="input-wrap">
                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <input :type="showPwd ? 'text' : 'password'" v-model="form.password" placeholder="••••••••"
                  @focus="focus.password = true" @blur="focus.password = false"/>
                <button type="button" class="eye-btn" @click="showPwd = !showPwd">
                  <svg v-if="!showPwd" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                  </svg>
                </button>
              </div>
              <span class="err-msg" v-if="form.errors.password">{{ form.errors.password }}</span>
            </div>

            <div class="form-options">
              <label class="remember">
                <input type="checkbox" v-model="form.remember"/>
                <span>Se souvenir de moi</span>
              </label>
              <a href="#" class="forgot">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn-submit" :class="{ loading: form.processing }">
              <span v-if="!form.processing">Se connecter</span>
              <div v-else class="spinner"></div>
            </button>

            <p class="switch-mode">
              Pas encore de compte ?
              <Link href="/register">S'inscrire gratuitement →</Link>
            </p>
          </form>

          <!-- Toast erreur global -->
          <transition name="toast">
            <div class="toast error" v-if="form.errors.message">
              <span>❌</span> {{ form.errors.message }}
            </div>
          </transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const showPwd = ref(false)
const canvas  = ref(null)
const focus   = ref({ email: false, password: false })

const form = useForm({
  email:    '',
  password: '',
  remember: false,
})

const features = [
  { icon: '🧊', label: 'Objets 3D interactifs', desc: 'Manipulez des modèles en temps réel' },
  { icon: '🎓', label: 'Cours structurés',       desc: 'Slides immersives et contenu riche' },
  { icon: '⚡', label: 'Apprentissage rapide',   desc: 'Mémorisation améliorée par la 3D' },
]

const stats = [
  { val: '2K+', label: 'Étudiants' },
  { val: '150+', label: 'Cours' },
  { val: '98%', label: 'Satisfaction' },
]

function handleLogin() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}

// ── Animated canvas background ───────────────────────────────────────────────
let animId = null
onMounted(() => {
  const c   = canvas.value
  const ctx = c.getContext('2d')
  let W = c.width  = window.innerWidth
  let H = c.height = window.innerHeight

  const resize = () => { W = c.width = window.innerWidth; H = c.height = window.innerHeight }
  window.addEventListener('resize', resize)

  const nodes = Array.from({ length: 40 }, () => ({
    x: Math.random() * W, y: Math.random() * H,
    vx: (Math.random() - 0.5) * 0.4, vy: (Math.random() - 0.5) * 0.4,
    r: Math.random() * 2 + 1,
  }))

  function draw() {
    ctx.clearRect(0, 0, W, H)
    for (let i = 0; i < nodes.length; i++) {
      for (let j = i + 1; j < nodes.length; j++) {
        const dx = nodes[i].x - nodes[j].x
        const dy = nodes[i].y - nodes[j].y
        const dist = Math.sqrt(dx * dx + dy * dy)
        if (dist < 140) {
          ctx.beginPath()
          ctx.strokeStyle = `rgba(0,245,212,${0.12 * (1 - dist / 140)})`
          ctx.lineWidth = 0.7
          ctx.moveTo(nodes[i].x, nodes[i].y)
          ctx.lineTo(nodes[j].x, nodes[j].y)
          ctx.stroke()
        }
      }
    }
    nodes.forEach(n => {
      ctx.beginPath()
      ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2)
      ctx.fillStyle = 'rgba(0,245,212,0.5)'
      ctx.fill()
      n.x += n.vx; n.y += n.vy
      if (n.x < 0 || n.x > W) n.vx *= -1
      if (n.y < 0 || n.y > H) n.vy *= -1
    })
    animId = requestAnimationFrame(draw)
  }
  draw()
})

onUnmounted(() => {
  cancelAnimationFrame(animId)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.auth-root {
  min-height: 100vh;
  background: #050b18;
  font-family: 'DM Sans', sans-serif;
  position: relative;
  overflow: hidden;
}

.bg-canvas {
  position: fixed;
  inset: 0;
  z-index: 0;
  opacity: 0.7;
}

.particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
.p { position: absolute; border-radius: 50%; animation: float linear infinite; opacity: 0; }
.p:nth-child(odd)  { background: rgba(0,245,212,0.15); }
.p:nth-child(even) { background: rgba(99,102,241,0.12); }
.p1  { width:4px; height:4px; left:8%;  animation-duration:18s; animation-delay:0s; }
.p2  { width:6px; height:6px; left:15%; animation-duration:22s; animation-delay:2s; }
.p3  { width:3px; height:3px; left:25%; animation-duration:15s; animation-delay:4s; }
.p4  { width:5px; height:5px; left:35%; animation-duration:20s; animation-delay:1s; }
.p5  { width:4px; height:4px; left:45%; animation-duration:17s; animation-delay:6s; }
.p6  { width:7px; height:7px; left:55%; animation-duration:25s; animation-delay:3s; }
.p7  { width:3px; height:3px; left:65%; animation-duration:14s; animation-delay:5s; }
.p8  { width:5px; height:5px; left:72%; animation-duration:19s; animation-delay:0.5s; }
.p9  { width:4px; height:4px; left:80%; animation-duration:21s; animation-delay:7s; }
.p10 { width:6px; height:6px; left:88%; animation-duration:16s; animation-delay:2.5s; }
.p11 { width:3px; height:3px; left:12%; animation-duration:23s; animation-delay:8s; }
.p12 { width:5px; height:5px; left:92%; animation-duration:18s; animation-delay:1.5s; }
.p13 { width:4px; height:4px; left:3%;  animation-duration:20s; animation-delay:9s; }
.p14 { width:6px; height:6px; left:48%; animation-duration:24s; animation-delay:4.5s; }
.p15 { width:3px; height:3px; left:58%; animation-duration:13s; animation-delay:6.5s; }
.p16 { width:5px; height:5px; left:78%; animation-duration:22s; animation-delay:3.5s; }
.p17 { width:4px; height:4px; left:32%; animation-duration:17s; animation-delay:7.5s; }
.p18 { width:7px; height:7px; left:68%; animation-duration:26s; animation-delay:0.8s; }

@keyframes float {
  0%   { transform: translateY(110vh) rotate(0deg); opacity: 0; }
  10%  { opacity: 1; }
  90%  { opacity: 1; }
  100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
}

.auth-layout {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
}

/* LEFT */
.left-panel {
  display: flex;
  flex-direction: column;
  padding: 2.5rem 3rem;
  background: linear-gradient(135deg, rgba(0,245,212,0.04) 0%, transparent 60%);
  border-right: 1px solid rgba(0,245,212,0.08);
}

.brand { display: flex; align-items: center; gap: 10px; animation: fadeUp 0.6s ease both; }
.logo-wrap {
  width: 44px; height: 44px;
  border-radius: 12px;
  background: rgba(0,245,212,0.08);
  border: 1px solid rgba(0,245,212,0.2);
  display: flex; align-items: center; justify-content: center;
}
.brand-name { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: #fff; letter-spacing: 0.05em; }
.brand-name em { color: #00f5d4; font-style: normal; }

.left-content { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 3rem 0; }

.hero-title {
  font-family: 'Syne', sans-serif;
  font-size: clamp(2.8rem, 4.5vw, 4rem);
  font-weight: 800;
  line-height: 1.05;
  color: #fff;
  margin-bottom: 1.4rem;
}
.hero-title .line { display: block; animation: fadeUp 0.7s ease both; }
.hero-title .line:nth-child(1) { animation-delay: 0.1s; }
.hero-title .line:nth-child(2) { animation-delay: 0.2s; }
.hero-title .line:nth-child(3) { animation-delay: 0.3s; }
.hero-title .line:nth-child(4) { animation-delay: 0.4s; }
.hero-title .accent  { color: #00f5d4; }
.hero-title .accent2 { background: linear-gradient(90deg, #6366f1, #a855f7); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

.hero-sub { color: rgba(255,255,255,0.5); font-size: 1.05rem; line-height: 1.65; max-width: 380px; margin-bottom: 2.5rem; animation: fadeUp 0.7s 0.5s ease both; }

.features { display: flex; flex-direction: column; gap: 1.1rem; }
.feat {
  display: flex; align-items: flex-start; gap: 14px;
  padding: 1rem 1.2rem;
  border-radius: 14px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  animation: fadeUp 0.7s ease both;
  transition: border-color 0.3s, background 0.3s;
}
.feat:nth-child(1) { animation-delay: 0.6s; }
.feat:nth-child(2) { animation-delay: 0.7s; }
.feat:nth-child(3) { animation-delay: 0.8s; }
.feat:hover { border-color: rgba(0,245,212,0.2); background: rgba(0,245,212,0.04); }
.feat-icon  { font-size: 1.4rem; flex-shrink: 0; }
.feat-title { color: #fff; font-weight: 500; font-size: 0.92rem; margin-bottom: 2px; }
.feat-desc  { color: rgba(255,255,255,0.4); font-size: 0.82rem; }

.left-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.06); }
.stats { display: flex; gap: 2.5rem; }
.stat { display: flex; flex-direction: column; gap: 2px; }
.stat-val   { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 700; color: #00f5d4; }
.stat-label { font-size: 0.78rem; color: rgba(255,255,255,0.4); }

/* RIGHT */
.right-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: rgba(5,11,24,0.6);
  backdrop-filter: blur(20px);
}

.card {
  width: 100%;
  max-width: 440px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 24px;
  padding: 2.2rem;
  position: relative;
  animation: fadeUp 0.7s 0.2s ease both;
  box-shadow: 0 40px 80px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.07);
}

.form-header { margin-bottom: 1.8rem; }
.form-header h2 { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
.form-header p  { color: rgba(255,255,255,0.4); font-size: 0.88rem; }

.field { margin-bottom: 1.1rem; }
.field label { display: block; font-size: 0.82rem; font-weight: 500; color: rgba(255,255,255,0.6); margin-bottom: 6px; letter-spacing: 0.02em; }

.input-wrap { position: relative; display: flex; align-items: center; }
.field-icon { position: absolute; left: 13px; width: 16px; height: 16px; color: rgba(255,255,255,0.3); transition: color 0.3s; }
.field.focused .field-icon { color: #00f5d4; }

.input-wrap input {
  width: 100%;
  padding: 0.72rem 2.8rem 0.72rem 2.6rem;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.92rem;
  outline: none;
  transition: border-color 0.3s, background 0.3s, box-shadow 0.3s;
}
.input-wrap input::placeholder { color: rgba(255,255,255,0.2); }
.field.focused .input-wrap input { border-color: rgba(0,245,212,0.4); background: rgba(0,245,212,0.04); box-shadow: 0 0 0 3px rgba(0,245,212,0.08); }
.field.error   .input-wrap input { border-color: rgba(239,68,68,0.5); }
.err-msg { font-size: 0.75rem; color: #f87171; margin-top: 4px; display: block; }

.eye-btn { position: absolute; right: 12px; background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.3); display: flex; align-items: center; transition: color 0.3s; }
.eye-btn:hover { color: rgba(255,255,255,0.7); }
.eye-btn svg { width: 16px; height: 16px; }

.form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; }
.remember { display: flex; align-items: center; gap: 7px; cursor: pointer; }
.remember input { accent-color: #00f5d4; }
.remember span  { font-size: 0.83rem; color: rgba(255,255,255,0.5); }
.forgot { font-size: 0.83rem; color: #00f5d4; text-decoration: none; opacity: 0.8; transition: opacity 0.2s; }
.forgot:hover { opacity: 1; }

.btn-submit {
  width: 100%;
  padding: 0.85rem;
  background: linear-gradient(135deg, #00f5d4, #00c4aa);
  border: none;
  border-radius: 12px;
  color: #050b18;
  font-family: 'Syne', sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
  display: flex; align-items: center; justify-content: center;
  min-height: 48px;
  letter-spacing: 0.02em;
}
.btn-submit:hover:not(.loading) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,245,212,0.3); }
.btn-submit.loading { opacity: 0.7; cursor: not-allowed; }

.spinner { width: 20px; height: 20px; border: 2px solid rgba(5,11,24,0.3); border-top-color: #050b18; border-radius: 50%; animation: spin 0.7s linear infinite; }

.switch-mode { text-align: center; font-size: 0.83rem; color: rgba(255,255,255,0.35); margin-top: 1rem; }
.switch-mode a { color: #00f5d4; text-decoration: none; font-weight: 500; transition: opacity 0.2s; }
.switch-mode a:hover { opacity: 0.75; }

.toast { position: absolute; bottom: 1.5rem; left: 50%; transform: translateX(-50%); padding: 0.7rem 1.2rem; border-radius: 10px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; white-space: nowrap; z-index: 10; }
.toast.error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }

.toast-enter-active, .toast-leave-active { transition: all 0.35s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(10px); }

@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin   { to   { transform: rotate(360deg); } }

@media (max-width: 900px) {
  .auth-layout { grid-template-columns: 1fr; }
  .left-panel  { display: none; }
  .right-panel { min-height: 100vh; }
}
</style>