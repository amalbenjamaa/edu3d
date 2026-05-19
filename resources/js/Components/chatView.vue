<template>
  <div class="chat-root">

    <!-- ── LISTE DES CONVERSATIONS ── -->
    <aside class="conv-panel">
      <div class="conv-header">
        <h3>Messages</h3>
        <span class="conv-badge">{{ totalUnread }}</span>
      </div>

      <div class="conv-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="searchConv" placeholder="Rechercher..."/>
      </div>

      <div class="conv-list">
        <!-- Nouveau message -->
        <div class="conv-new" @click="showNewConv = true" v-if="isTeacher">
          <div class="new-icon">＋</div>
          <span>Nouveau message</span>
        </div>

        <div
          v-for="conv in filteredConversations"
          :key="conv.id"
          :class="['conv-item', { active: activeConv?.id === conv.id, unread: conv.unreadCount > 0 }]"
          @click="openConv(conv)"
        >
          <div class="conv-avatar" :style="{ background: avatarColor(conv.name) }">
            {{ conv.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="conv-info">
            <div class="conv-name">{{ conv.name }}</div>
            <div class="conv-last">{{ conv.lastMessage || 'Aucun message' }}</div>
          </div>
          <div class="conv-meta">
            <div class="conv-time">{{ conv.lastTime }}</div>
            <div class="conv-unread" v-if="conv.unreadCount > 0">{{ conv.unreadCount }}</div>
          </div>
        </div>

        <div class="conv-empty" v-if="!filteredConversations.length">
          <p>Aucune conversation</p>
        </div>
      </div>
    </aside>

    <!-- ── ZONE DE CHAT ── -->
    <main class="chat-area">

      <!-- Pas de conv sélectionnée -->
      <div class="chat-empty" v-if="!activeConv">
        <div class="chat-empty-icon">
          <svg viewBox="0 0 80 80" width="80" height="80">
            <path d="M40 10 C20 10 10 22 10 35 C10 48 20 58 35 60 L35 70 L48 60 C62 58 70 48 70 35 C70 22 60 10 40 10Z"
              fill="none" stroke="rgba(167,139,250,0.3)" stroke-width="2"/>
            <circle cx="28" cy="35" r="3" fill="rgba(167,139,250,0.4)"/>
            <circle cx="40" cy="35" r="3" fill="rgba(167,139,250,0.4)"/>
            <circle cx="52" cy="35" r="3" fill="rgba(167,139,250,0.4)"/>
          </svg>
        </div>
        <h3>Sélectionnez une conversation</h3>
        <p>{{ isTeacher ? 'Choisissez un étudiant ou créez un nouveau message' : 'Cliquez sur une conversation pour commencer' }}</p>
      </div>

      <template v-else>
        <!-- Header chat -->
        <div class="chat-header">
          <div class="chat-header-left">
            <div class="chat-avatar" :style="{ background: avatarColor(activeConv.name) }">
              {{ activeConv.name?.charAt(0)?.toUpperCase() }}
            </div>
            <div>
              <div class="chat-name">{{ activeConv.name }}</div>
              <div class="chat-status">
                <span class="status-dot" :class="{ online: activeConv.online }"></span>
                {{ activeConv.online ? 'En ligne' : 'Hors ligne' }}
              </div>
            </div>
          </div>
          <div class="chat-header-actions">
            <button class="chat-action-btn" @click="clearMessages" title="Effacer la conversation">🗑️</button>
          </div>
        </div>

        <!-- Messages -->
        <div class="messages-area" ref="messagesArea">
          <!-- Date séparateur -->
          <div class="date-sep">Aujourd'hui</div>

          <div v-if="!currentMessages.length" class="no-messages">
            <p>Commencez la conversation 👋</p>
          </div>

          <div
            v-for="(msg, i) in currentMessages"
            :key="msg.id"
            :class="['msg-wrap', { own: msg.own }]"
          >
            <!-- Avatar pour les messages reçus -->
            <div class="msg-avatar" v-if="!msg.own" :style="{ background: avatarColor(activeConv.name) }">
              {{ activeConv.name?.charAt(0)?.toUpperCase() }}
            </div>

            <div class="msg-bubble-wrap">
              <!-- Heure si différente du msg précédent -->
              <div class="msg-time-label" v-if="shouldShowTime(msg, currentMessages[i-1])">
                {{ msg.time }}
              </div>
              <div :class="['msg-bubble', { own: msg.own, system: msg.system }]">
                <!-- Fichier / 3D -->
                <div class="msg-file" v-if="msg.file">
                  <div class="file-icon">{{ msg.file.type === '3d' ? '🧊' : '📎' }}</div>
                  <div class="file-info">
                    <div class="file-name">{{ msg.file.name }}</div>
                    <a :href="msg.file.url" target="_blank" class="file-link">Ouvrir →</a>
                  </div>
                </div>
                <!-- Texte -->
                <span v-else>{{ msg.text }}</span>
              </div>
              <div class="msg-status" v-if="msg.own">
                {{ msg.sent ? '✓✓' : '✓' }}
              </div>
            </div>
          </div>

          <!-- Typing indicator -->
          <div class="typing-indicator" v-if="isTyping">
            <div class="typing-avatar" :style="{ background: avatarColor(activeConv.name) }">
              {{ activeConv.name?.charAt(0)?.toUpperCase() }}
            </div>
            <div class="typing-dots">
              <span></span><span></span><span></span>
            </div>
          </div>
        </div>

        <!-- Input zone -->
        <div class="input-zone">
          <!-- Attachments bar -->
          <div class="attach-bar" v-if="showAttach">
            <button class="attach-opt" @click="attachModel">
              🧊 Partager un modèle 3D
            </button>
            <button class="attach-opt" @click="attachFile">
              📎 Joindre un fichier
            </button>
            <input ref="fileInput" type="file" style="display:none" @change="handleFileAttach"/>
          </div>

          <div class="input-row">
            <button class="attach-toggle" @click="showAttach = !showAttach" :class="{ active: showAttach }">
              ＋
            </button>

            <div class="input-wrap">
              <textarea
                ref="inputRef"
                v-model="newMessage"
                :placeholder="`Message à ${activeConv.name}...`"
                class="msg-input"
                rows="1"
                @keydown.enter.exact.prevent="sendMessage"
                @keydown.enter.shift.exact="newMessage += '\n'"
                @input="autoResize"
              ></textarea>

              <!-- Emoji picker simple -->
              <button class="emoji-btn" @click="showEmoji = !showEmoji">😊</button>
            </div>

            <button
              class="send-btn"
              :class="{ active: newMessage.trim() }"
              @click="sendMessage"
              :disabled="!newMessage.trim() && !pendingAttachment"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22,2 15,22 11,13 2,9"/>
              </svg>
            </button>
          </div>

          <!-- Emoji panel simplifié -->
          <div class="emoji-panel" v-if="showEmoji">
            <button
              v-for="e in emojis"
              :key="e"
              class="emoji"
              @click="insertEmoji(e)"
            >{{ e }}</button>
          </div>

          <!-- Pièce jointe en attente -->
          <div class="pending-attachment" v-if="pendingAttachment">
            <span>{{ pendingAttachment.type === '3d' ? '🧊' : '📎' }} {{ pendingAttachment.name }}</span>
            <button @click="pendingAttachment = null">✕</button>
          </div>

          <div class="input-hint">
            Entrée pour envoyer · Maj+Entrée pour nouvelle ligne
          </div>
        </div>
      </template>
    </main>

    <!-- ── MODAL NOUVEAU MESSAGE ── -->
    <transition name="modal">
      <div class="modal-overlay" v-if="showNewConv" @click.self="showNewConv = false">
        <div class="modal-box">
          <div class="modal-header">
            <h3>💬 Nouveau message</h3>
            <button class="modal-close" @click="showNewConv = false">✕</button>
          </div>
          <div class="modal-body">
            <div class="field-group">
              <label>Destinataire</label>
              <div class="search-recipient">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input v-model="recipientSearch" placeholder="Nom ou email de l'étudiant..." class="f-input"/>
              </div>
              <div class="recipient-list" v-if="filteredRecipients.length">
                <div
                  v-for="r in filteredRecipients"
                  :key="r.id"
                  :class="['recipient-item', { selected: selectedRecipient?.id === r.id }]"
                  @click="selectedRecipient = r"
                >
                  <div class="r-avatar" :style="{ background: avatarColor(r.nom) }">{{ r.nom?.charAt(0) }}</div>
                  <div class="r-info">
                    <div class="r-name">{{ r.nom }}</div>
                    <div class="r-email">{{ r.email }}</div>
                  </div>
                  <div class="r-check" v-if="selectedRecipient?.id === r.id">✓</div>
                </div>
              </div>
            </div>
            <div class="field-group" v-if="selectedRecipient">
              <label>Premier message</label>
              <textarea v-model="firstMessage" class="f-textarea" rows="3" placeholder="Bonjour..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="showNewConv = false">Annuler</button>
            <button class="btn-send-new" @click="startNewConversation" :disabled="!selectedRecipient || !firstMessage.trim()">
              Envoyer →
            </button>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import api from '@/services/api.js'

const props = defineProps({
  isTeacher: { type: Boolean, default: false },
})

// ── State ─────────────────────────────────────────────────
const conversations   = ref([])
const activeConv      = ref(null)
const allMessages     = ref({}) // { convId: [msg, ...] }
const newMessage      = ref('')
const searchConv      = ref('')
const isTyping        = ref(false)
const showAttach      = ref(false)
const showEmoji       = ref(false)
const showNewConv     = ref(false)
const recipientSearch = ref('')
const selectedRecipient = ref(null)
const firstMessage    = ref('')
const pendingAttachment = ref(null)
const messagesArea    = ref(null)
const inputRef        = ref(null)
const fileInput       = ref(null)
let   pollingInterval = null
let   typingTimer     = null

const emojis = ['😊','👍','❓','✅','❌','🎓','📚','🧊','💡','🔥','👏','🙏','😅','🤔','📝','⚡']

// ── Computed ──────────────────────────────────────────────
const filteredConversations = computed(() => {
  if (!searchConv.value) return conversations.value
  const q = searchConv.value.toLowerCase()
  return conversations.value.filter(c =>
    c.name?.toLowerCase().includes(q) ||
    c.lastMessage?.toLowerCase().includes(q)
  )
})

const currentMessages = computed(() =>
  allMessages.value[activeConv.value?.id] || []
)

const totalUnread = computed(() =>
  conversations.value.reduce((acc, c) => acc + (c.unreadCount || 0), 0)
)

const allUsers = ref([])
const filteredRecipients = computed(() => {
  if (!recipientSearch.value) return allUsers.value
  const q = recipientSearch.value.toLowerCase()
  return allUsers.value.filter(u =>
    u.nom?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q)
  )
})

// ── Load ──────────────────────────────────────────────────
async function loadConversations() {
  try {
    const res = await api.get('/messages/conversations')
    conversations.value = res.data.map(c => ({
      ...c,
      lastTime: formatTime(c.lastMessageAt),
    }))
  } catch (e) {
    console.error("Erreur de chargement des conversations", e)
  }
}

async function loadMessages(convId) {
  try {
    const res = await api.get(`/messages/conversations/${convId}`)
    allMessages.value[convId] = res.data.map(m => ({
      ...m,
      own:  m.senderId === getCurrentUserId() || m.senderId == getCurrentUserId(),
      time: formatTime(m.createdAt),
      sent: true,
    }))
  } catch {
    if (!allMessages.value[convId]) {
      allMessages.value[convId] = []
    }
  }
  scrollToBottom()
}

async function loadUsers() {
  try {
    const res = await api.get('/users')
    const myEmail = localStorage.getItem('userName')
    allUsers.value = res.data.filter(u => u.email !== myEmail && u.role !== 'ADMIN')
  } catch {
    allUsers.value = []
  }
}

// ── Actions ───────────────────────────────────────────────
async function openConv(conv) {
  activeConv.value = conv
  conv.unreadCount = 0
  showEmoji.value  = false
  showAttach.value = false
  await loadMessages(conv.id)
}

async function sendMessage() {
  const text = newMessage.value.trim()
  if (!text && !pendingAttachment.value) return

  const msg = {
    id:   Date.now(),
    own:  true,
    text: text || null,
    file: pendingAttachment.value || null,
    time: formatTime(new Date()),
    sent: false,
  }

  // Ajouter localement immédiatement
  if (!allMessages.value[activeConv.value.id]) {
    allMessages.value[activeConv.value.id] = []
  }
  allMessages.value[activeConv.value.id].push(msg)

  // Mettre à jour la preview
  const conv = conversations.value.find(c => c.id === activeConv.value.id)
  if (conv) { conv.lastMessage = text || '📎 Fichier'; conv.lastTime = 'Maintenant' }

  newMessage.value    = ''
  pendingAttachment.value = null
  showAttach.value    = false
  showEmoji.value     = false

  scrollToBottom()

  // Envoyer à l'API
  try {
    const payload = {
      receiverId:     activeConv.value.userId,
      text:           text || null,
      fileUrl:        msg.file?.url || null,
      fileName:       msg.file?.name || null,
    }
    await api.post('/messages', payload)
    msg.sent = true
  } catch {
    console.error("Message error")
  }

  if (inputRef.value) {
    inputRef.value.style.height = 'auto'
    inputRef.value.focus()
  }
}

async function startNewConversation() {
  if (!selectedRecipient.value || !firstMessage.value.trim()) return

  // Créer la conv localement
  const convId = 'new-' + Date.now()
  const newConv = {
    id:           convId,
    name:         selectedRecipient.value.nom,
    userId:       selectedRecipient.value.id,
    lastMessage:  firstMessage.value,
    lastTime:     'Maintenant',
    unreadCount:  0,
    online:       false,
  }

  conversations.value.unshift(newConv)
  allMessages.value[convId] = []

  try {
    const res = await api.post('/messages/conversations', {
      receiverId: selectedRecipient.value.id
    })
    newConv.id = res.data.id
  } catch (e) { console.error(e) }

  showNewConv.value     = false
  selectedRecipient.value = null
  firstMessage.value    = ''
  recipientSearch.value = ''

  await openConv(newConv)
  newMessage.value = firstMessage.value || ''
  await sendMessage()
}

function clearMessages() {
  if (confirm(`Effacer la conversation avec ${activeConv.value.name} ?`)) {
    allMessages.value[activeConv.value.id] = []
  }
}

// ── Attachments ───────────────────────────────────────────
function attachModel() {
  const url = prompt('URL du modèle 3D (.glb) :')
  if (url) {
    pendingAttachment.value = { type: '3d', name: url.split('/').pop(), url }
  }
  showAttach.value = false
}

function attachFile() {
  fileInput.value?.click()
  showAttach.value = false
}

function handleFileAttach(e) {
  const file = e.target.files[0]
  if (!file) return
  const url = URL.createObjectURL(file)
  pendingAttachment.value = { type: 'file', name: file.name, url }
}

// ── Emoji ─────────────────────────────────────────────────
function insertEmoji(e) {
  newMessage.value += e
  showEmoji.value = false
  inputRef.value?.focus()
}

// ── UI helpers ────────────────────────────────────────────
function autoResize(e) {
  const el = e.target
  el.style.height = 'auto'
  el.style.height = Math.min(el.scrollHeight, 120) + 'px'
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesArea.value) {
      messagesArea.value.scrollTop = messagesArea.value.scrollHeight
    }
  })
}

function shouldShowTime(msg, prev) {
  if (!prev) return true
  return msg.time !== prev.time
}

function formatTime(date) {
  if (!date) return ''
  const d = date instanceof Date ? date : new Date(date)
  if (isNaN(d)) return ''
  return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function avatarColor(name) {
  const colors = [
    'linear-gradient(135deg,#a78bfa,#7c3aed)',
    'linear-gradient(135deg,#34d399,#059669)',
    'linear-gradient(135deg,#60a5fa,#2563eb)',
    'linear-gradient(135deg,#f472b6,#db2777)',
    'linear-gradient(135deg,#fb923c,#ea580c)',
    'linear-gradient(135deg,#00f5d4,#0891b2)',
  ]
  const idx = (name?.charCodeAt(0) || 0) % colors.length
  return colors[idx]
}

function getCurrentUserId() {
  return localStorage.getItem('userId') || 'me'
}



// ── Polling pour nouveaux messages ─────────────────────────
function startPolling() {
  pollingInterval = setInterval(async () => {
    if (!activeConv.value) return
    try {
      await loadMessages(activeConv.value.id)
    } catch {}
  }, 10000) // toutes les 10s
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  await loadConversations()
  await loadUsers()
  startPolling()
})

onUnmounted(() => {
  clearInterval(pollingInterval)
})

watch(activeConv, () => {
  scrollToBottom()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');
* { box-sizing: border-box; margin: 0; padding: 0; }

/* ── ROOT ── */
.chat-root {
  display: grid;
  grid-template-columns: 290px 1fr;
  height: 100%;
  min-height: 0;
  overflow: hidden;
  font-family: 'DM Sans', sans-serif;
  color: #fff;
}

/* ── CONVERSATIONS PANEL ── */
.conv-panel {
  border-right: 1px solid rgba(255,255,255,0.06);
  display: flex; flex-direction: column;
  background: rgba(255,255,255,0.02);
  overflow: hidden;
}
.conv-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.1rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.conv-header h3 { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; }
.conv-badge {
  background: rgba(167,139,250,0.2); color: #a78bfa;
  font-size: 0.72rem; padding: 2px 8px; border-radius: 12px;
  display: none;
}
.conv-badge:not(:empty) { display: block; }

.conv-search {
  display: flex; align-items: center; gap: 7px;
  margin: 0.6rem 0.8rem;
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
  border-radius: 9px; padding: 0.45rem 0.75rem;
}
.conv-search svg { color: rgba(255,255,255,0.28); flex-shrink: 0; }
.conv-search input {
  background: none; border: none; outline: none; color: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 0.83rem; width: 100%;
}
.conv-search input::placeholder { color: rgba(255,255,255,0.22); }

.conv-list { flex: 1; overflow-y: auto; padding: 0.4rem 0.5rem; display: flex; flex-direction: column; gap: 2px; }

.conv-new {
  display: flex; align-items: center; gap: 10px;
  padding: 0.65rem 0.8rem;
  border-radius: 9px; cursor: pointer;
  border: 1px dashed rgba(167,139,250,0.25);
  color: rgba(167,139,250,0.6); font-size: 0.83rem;
  transition: all 0.2s; margin-bottom: 4px;
}
.conv-new:hover { border-color: #a78bfa; color: #a78bfa; background: rgba(167,139,250,0.06); }
.new-icon { font-size: 1.1rem; }

.conv-item {
  display: flex; align-items: center; gap: 10px;
  padding: 0.75rem 0.8rem;
  border-radius: 10px; cursor: pointer;
  border: 1px solid transparent; transition: all 0.2s;
}
.conv-item:hover { background: rgba(255,255,255,0.04); }
.conv-item.active { background: rgba(167,139,250,0.08); border-color: rgba(167,139,250,0.2); }
.conv-item.unread .conv-name { font-weight: 600; color: #fff; }
.conv-item.unread .conv-last { color: rgba(255,255,255,0.5); }

.conv-avatar {
  width: 38px; height: 38px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.conv-info { flex: 1; min-width: 0; }
.conv-name { font-size: 0.84rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-last { font-size: 0.73rem; color: rgba(255,255,255,0.32); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
.conv-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.conv-time { font-size: 0.68rem; color: rgba(255,255,255,0.25); }
.conv-unread {
  background: #a78bfa; color: #fff;
  font-size: 0.65rem; font-weight: 700;
  width: 18px; height: 18px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.conv-empty { padding: 2rem; text-align: center; color: rgba(255,255,255,0.2); font-size: 0.83rem; }

/* ── CHAT AREA ── */
.chat-area {
  display: flex; flex-direction: column;
  overflow: hidden; background: #060918;
}

.chat-empty {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 12px; padding: 2rem; text-align: center;
}
.chat-empty-icon { margin-bottom: 0.5rem; opacity: 0.5; }
.chat-empty h3 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 600; color: rgba(255,255,255,0.6); }
.chat-empty p  { font-size: 0.82rem; color: rgba(255,255,255,0.28); max-width: 260px; line-height: 1.5; }

/* ── CHAT HEADER ── */
.chat-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.8rem 1.2rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  background: rgba(255,255,255,0.02); flex-shrink: 0;
}
.chat-header-left { display: flex; align-items: center; gap: 10px; }
.chat-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.chat-name   { font-size: 0.88rem; font-weight: 600; }
.chat-status { display: flex; align-items: center; gap: 5px; font-size: 0.72rem; color: rgba(255,255,255,0.35); margin-top: 2px; }
.status-dot  { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,0.2); }
.status-dot.online { background: #34d399; box-shadow: 0 0 6px rgba(52,211,153,0.6); }
.chat-action-btn {
  background: none; border: none; cursor: pointer; font-size: 1.1rem; padding: 5px;
  border-radius: 7px; transition: background 0.2s;
}
.chat-action-btn:hover { background: rgba(255,255,255,0.07); }

/* ── MESSAGES ── */
.messages-area {
  flex: 1; overflow-y: auto; padding: 1.2rem;
  display: flex; flex-direction: column; gap: 4px;
  scroll-behavior: smooth;
}

.date-sep {
  text-align: center; font-size: 0.72rem; color: rgba(255,255,255,0.25);
  margin: 0.5rem 0; position: relative;
}
.date-sep::before, .date-sep::after {
  content: ''; position: absolute; top: 50%; width: 40%; height: 1px;
  background: rgba(255,255,255,0.06);
}
.date-sep::before { left: 0; }
.date-sep::after  { right: 0; }

.no-messages {
  flex: 1; display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,0.2); font-size: 0.83rem;
}

.msg-wrap {
  display: flex; align-items: flex-end; gap: 8px;
  margin-bottom: 4px;
}
.msg-wrap.own { flex-direction: row-reverse; }

.msg-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 700; color: #fff; flex-shrink: 0;
}

.msg-bubble-wrap { display: flex; flex-direction: column; max-width: 65%; }
.msg-wrap.own .msg-bubble-wrap { align-items: flex-end; }

.msg-time-label { font-size: 0.65rem; color: rgba(255,255,255,0.2); margin-bottom: 3px; padding: 0 4px; }

.msg-bubble {
  padding: 0.55rem 0.9rem;
  border-radius: 16px;
  font-size: 0.85rem; line-height: 1.55;
  word-break: break-word;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.06);
  color: rgba(255,255,255,0.85);
}
.msg-bubble.own {
  background: linear-gradient(135deg, rgba(167,139,250,0.25), rgba(124,58,237,0.2));
  border-color: rgba(167,139,250,0.2);
  color: #fff;
  border-radius: 16px 16px 4px 16px;
}
.msg-bubble:not(.own):not(.system) { border-radius: 16px 16px 16px 4px; }

.msg-status { font-size: 0.65rem; color: rgba(167,139,250,0.5); margin-top: 2px; }

/* Fichier dans le message */
.msg-file {
  display: flex; align-items: center; gap: 10px;
  padding: 0.4rem 0.6rem;
  background: rgba(255,255,255,0.05); border-radius: 10px;
}
.file-icon { font-size: 1.4rem; }
.file-name { font-size: 0.8rem; font-weight: 500; }
.file-link { font-size: 0.72rem; color: #a78bfa; text-decoration: none; }
.file-link:hover { text-decoration: underline; }

/* Typing indicator */
.typing-indicator {
  display: flex; align-items: flex-end; gap: 8px; margin-top: 4px;
}
.typing-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 700; color: #fff;
}
.typing-dots {
  display: flex; align-items: center; gap: 4px;
  background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.06);
  border-radius: 16px 16px 16px 4px; padding: 0.6rem 0.9rem;
}
.typing-dots span {
  width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,0.4);
  animation: bounce 1.2s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes bounce { 0%,80%,100%{ transform:translateY(0); } 40%{ transform:translateY(-6px); } }

/* ── INPUT ZONE ── */
.input-zone {
  border-top: 1px solid rgba(255,255,255,0.06);
  padding: 0.8rem 1rem;
  background: rgba(6,9,26,0.8); flex-shrink: 0;
}

.attach-bar {
  display: flex; gap: 6px; margin-bottom: 8px;
  animation: slideUp 0.2s ease;
}
@keyframes slideUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }
.attach-opt {
  padding: 0.45rem 0.9rem; background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.09); border-radius: 20px;
  color: rgba(255,255,255,0.55); font-size: 0.78rem; cursor: pointer; transition: all 0.2s;
}
.attach-opt:hover { border-color: rgba(167,139,250,0.3); color: #a78bfa; }

.input-row { display: flex; align-items: flex-end; gap: 8px; }

.attach-toggle {
  width: 36px; height: 36px; flex-shrink: 0;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 50%; color: rgba(255,255,255,0.45); font-size: 1.2rem;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s; line-height: 1;
}
.attach-toggle:hover, .attach-toggle.active { background: rgba(167,139,250,0.12); border-color: rgba(167,139,250,0.3); color: #a78bfa; }

.input-wrap {
  flex: 1; display: flex; align-items: center;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 18px; padding: 0.5rem 0.85rem; gap: 6px;
  transition: border-color 0.2s;
}
.input-wrap:focus-within { border-color: rgba(167,139,250,0.3); }

.msg-input {
  flex: 1; background: none; border: none; outline: none;
  color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.88rem;
  resize: none; max-height: 120px; line-height: 1.5;
  scrollbar-width: thin;
}
.msg-input::placeholder { color: rgba(255,255,255,0.22); }

.emoji-btn {
  background: none; border: none; cursor: pointer; font-size: 1.1rem;
  padding: 2px; border-radius: 5px; flex-shrink: 0;
  opacity: 0.5; transition: opacity 0.2s;
}
.emoji-btn:hover { opacity: 1; }

.send-btn {
  width: 38px; height: 38px; flex-shrink: 0;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 50%; color: rgba(255,255,255,0.3); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.send-btn.active {
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  border-color: transparent; color: #fff;
}
.send-btn.active:hover { box-shadow: 0 4px 14px rgba(167,139,250,0.35); }
.send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Emoji panel */
.emoji-panel {
  display: flex; flex-wrap: wrap; gap: 3px;
  background: rgba(13,18,40,0.95); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px; padding: 0.5rem;
  position: absolute; bottom: 100%; margin-bottom: 6px;
  animation: slideUp 0.2s ease;
}
.emoji {
  background: none; border: none; cursor: pointer; font-size: 1.2rem;
  padding: 4px; border-radius: 6px; transition: background 0.15s;
}
.emoji:hover { background: rgba(255,255,255,0.08); }

.input-hint { font-size: 0.67rem; color: rgba(255,255,255,0.15); text-align: center; margin-top: 5px; }

.pending-attachment {
  display: flex; align-items: center; justify-content: space-between;
  padding: 5px 10px; margin-top: 6px;
  background: rgba(167,139,250,0.08); border: 1px solid rgba(167,139,250,0.2);
  border-radius: 8px; font-size: 0.78rem; color: #a78bfa;
}
.pending-attachment button {
  background: none; border: none; color: rgba(255,255,255,0.35); cursor: pointer; font-size: 0.8rem;
}

/* ── MODAL NOUVEAU MESSAGE ── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(6,9,26,0.88);
  backdrop-filter: blur(8px); display: flex; align-items: center;
  justify-content: center; z-index: 300;
}
.modal-box {
  background: #0d1228; border: 1px solid rgba(167,139,250,0.15);
  border-radius: 18px; width: 100%; max-width: 460px;
  overflow: hidden; animation: popIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes popIn { from { opacity:0; transform:scale(0.88) translateY(20px); } to { opacity:1; transform:none; } }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.06); }
.modal-header h3 { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 600; }
.modal-close { background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.3); font-size: 0.9rem; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.modal-close:hover { background: rgba(255,255,255,0.06); color: #fff; }
.modal-body { padding: 1.2rem; max-height: 60vh; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
.modal-footer { display: flex; gap: 8px; justify-content: flex-end; padding: 0.9rem 1.2rem; border-top: 1px solid rgba(255,255,255,0.06); }

.field-group { display: flex; flex-direction: column; gap: 5px; }
.field-group label { font-size: 0.75rem; color: rgba(255,255,255,0.4); font-weight: 500; }

.search-recipient {
  display: flex; align-items: center; gap: 7px;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 9px; padding: 0.55rem 0.85rem;
}
.search-recipient svg { color: rgba(255,255,255,0.28); flex-shrink: 0; }
.f-input {
  background: none; border: none; outline: none; color: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 0.85rem; width: 100%;
}
.f-input::placeholder { color: rgba(255,255,255,0.2); }

.recipient-list { display: flex; flex-direction: column; gap: 3px; max-height: 180px; overflow-y: auto; margin-top: 4px; }
.recipient-item {
  display: flex; align-items: center; gap: 10px;
  padding: 0.55rem 0.8rem; border-radius: 8px; cursor: pointer;
  border: 1px solid transparent; transition: all 0.2s;
}
.recipient-item:hover { background: rgba(255,255,255,0.04); }
.recipient-item.selected { background: rgba(167,139,250,0.08); border-color: rgba(167,139,250,0.2); }
.r-avatar { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #fff; flex-shrink: 0; }
.r-info { flex: 1; min-width: 0; }
.r-name  { font-size: 0.83rem; font-weight: 500; }
.r-email { font-size: 0.72rem; color: rgba(255,255,255,0.35); }
.r-check { color: #a78bfa; font-size: 0.9rem; }

.f-textarea {
  width: 100%; padding: 0.6rem 0.85rem; resize: vertical;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09);
  border-radius: 9px; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 0.85rem;
  outline: none;
}
.f-textarea::placeholder { color: rgba(255,255,255,0.2); }
.f-textarea:focus { border-color: rgba(167,139,250,0.4); }

.btn-cancel {
  padding: 0.55rem 1rem; background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.09); border-radius: 9px;
  color: rgba(255,255,255,0.5); font-family: 'DM Sans', sans-serif;
  font-size: 0.85rem; cursor: pointer;
}
.btn-send-new {
  padding: 0.55rem 1.2rem;
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  border: none; border-radius: 9px; color: #fff;
  font-family: 'Syne', sans-serif; font-size: 0.85rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.btn-send-new:hover:not(:disabled) { box-shadow: 0 4px 14px rgba(167,139,250,0.3); }
.btn-send-new:disabled { opacity: 0.4; cursor: not-allowed; }

/* Transitions */
.modal-enter-active,.modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from,.modal-leave-to { opacity: 0; }
</style>