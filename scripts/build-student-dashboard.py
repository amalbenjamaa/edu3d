from pathlib import Path
import re

raw_path = Path(__file__).resolve().parents[1] / "resources/js/Pages/Student/Dashboard.vue.raw"
out_path = Path(__file__).resolve().parents[1] / "resources/js/Pages/Student/Dashboard.vue"

raw = raw_path.read_text(encoding="utf-8")

messages_pattern = r"        <!-- ====== MESSAGES ====== -->.*?(?=        <!-- ====== PROFILE)"
replacement = """        <!-- ====== MESSAGES ====== -->
        <motion v-else-if="activeView === 'messages'" key="messages" class="view view-full view-chat">
          <ChatView :is-teacher="false" />
        </motion>

        """
text = re.sub(messages_pattern, replacement, raw, count=1, flags=re.DOTALL)
text = text.replace("<motion", "<motion").replace("</motion>", "</motion>")
text = text.replace("<motion", "<div").replace("</motion>", "</div>")

script = r'''
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import ChatView from './ChatView.vue'

const props = defineProps({ auth: Object })

const sidebarCollapsed = ref(false)
const activeView = ref('overview')
const isLoading = ref(false)
const currentTime = ref('')
const inviteCode = ref('')

const currentStudent = ref({ id: null, name: '', email: '', role: 'student' })
const myClassrooms = ref([])
const myCourses = ref([])
const toast = ref({ show: false, msg: '', type: 'success' })

const classroomDetail = ref({ show: false, cls: null, teacher: null, course: null })
const courseView = ref({ show: false, course: null, slides: [], activeSlide: null })

const profileForm = ref({ name: '', email: '' })

const iGrid = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`
const iClass = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>`
const iBook = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>`
const iMsg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`
const iProfile = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`

const navItems = computed(() => [
  { id: 'overview', label: 'Vue globale', icon: iGrid },
  { id: 'classrooms', label: 'Mes Classes', icon: iClass, badge: myClassrooms.value.length || null },
  { id: 'courses', label: 'Mes Cours', icon: iBook, badge: myCourses.value.length || null },
  { id: 'messages', label: 'Messages', icon: iMsg },
  { id: 'profile', label: 'Mon Profil', icon: iProfile },
])

const currentPageTitle = computed(() => ({
  overview: 'Vue globale', classrooms: 'Mes Classes',
  courses: 'Mes Cours', messages: 'Messages', profile: 'Mon Profil',
})[activeView.value] || '')

const studentInitial = computed(() => currentStudent.value.name?.charAt(0)?.toUpperCase() || 'S')

const statsCards = computed(() => [
  { label: 'Mes classes', val: myClassrooms.value.length, pct: 60, color: '#34d399', bg: 'rgba(52,211,153,0.1)', icon: iClass },
  { label: 'Mes cours', val: myCourses.value.length, pct: 70, color: '#60a5fa', bg: 'rgba(96,165,250,0.1)', icon: iBook },
  { label: 'Total slides', val: myCourses.value.reduce((a, c) => a + (c.slideCount || 0), 0), pct: 50, color: '#a78bfa', bg: 'rgba(167,139,250,0.1)', icon: iGrid },
  { label: 'Enseignants', val: myTeachers.value.length, pct: 40, color: '#f472b6', bg: 'rgba(244,114,182,0.1)', icon: iProfile },
])

const myTeachers = computed(() => {
  const map = new Map()
  myCourses.value.forEach(c => {
    const name = c.teacherName || c.course?.teacher?.name
    const email = c.course?.teacher?.email || ''
    if (name && !map.has(name)) map.set(name, { nom: name, email })
  })
  return Array.from(map.values())
})

function mapClassroom(cls) {
  return {
    ...cls,
    course: cls.course,
    courseId: cls.course_id ?? cls.course?.id,
    studentCount: cls.enrollments_count ?? 0,
  }
}

function mapCourse(c) {
  const classroom = c.classroom ?? myClassrooms.value.find(cl => cl.course?.id === c.course_id)
  return {
    ...c,
    teacherName: c.course?.teacher?.name ?? c.teacher?.name ?? 'Enseignant',
    slideCount: c.slideCount ?? c.slides_count ?? 0,
    classroom,
  }
}

function showToast(msg, type = 'success') {
  toast.value = { show: true, msg, type }
  setTimeout(() => toast.value.show = false, 3000)
}

function loadStudent() {
  const u = props.auth?.user ?? {}
  currentStudent.value = { id: u.id, name: u.name, email: u.email, role: u.role }
  profileForm.value = { name: u.name ?? '', email: u.email ?? '' }
}

async function loadMyClassrooms() {
  try {
    const { data } = await axios.get('/api/classrooms')
    myClassrooms.value = (data.data ?? data).map(mapClassroom)
  } catch {
    myClassrooms.value = []
  }
}

async function loadMyCourses() {
  try {
    const { data } = await axios.get('/api/enrollments')
    const enrollments = data.data ?? data
    const coursesMap = new Map()
    for (const e of enrollments) {
      if (!['active', 'completed'].includes(e.status)) continue
      const course = e.classroom?.course
      if (!course) continue
      const slideRes = await axios.get(`/api/classrooms/${e.classroom_id}/slides`).catch(() => ({ data: { data: [] } }))
      const slides = slideRes.data?.data ?? slideRes.data ?? []
      coursesMap.set(course.id, mapCourse({
        ...course,
        classroom: e.classroom,
        slideCount: slides.length,
      }))
    }
    myCourses.value = Array.from(coursesMap.values())
  } catch {
    myCourses.value = []
  }
}

async function joinByCode() {
  const id = parseInt(String(inviteCode.value).trim(), 10)
  if (!id) {
    showToast("Entrez l'ID de la classe fourni par l'enseignant", 'error')
    return
  }
  isLoading.value = true
  try {
    await axios.post('/api/enrollments', { classroom_id: id })
    showToast('Classe rejointe avec succès !')
    inviteCode.value = ''
    await loadMyClassrooms()
    await loadMyCourses()
  } catch (e) {
    showToast(e.response?.data?.message || 'Impossible de rejoindre cette classe', 'error')
  } finally {
    isLoading.value = false
  }
}

function getCourseOfClass(cls) {
  return myCourses.value.find(c => c.id === cls?.course?.id || c.id === cls?.courseId)
}

function getTeacherOfClass(cls) {
  return getCourseOfClass(cls)?.teacherName ?? null
}

async function openClassroomDetail(cls) {
  const course = getCourseOfClass(cls)
  classroomDetail.value = {
    show: true,
    cls,
    teacher: getTeacherOfClass(cls) ? { nom: getTeacherOfClass(cls), email: '' } : null,
    course: course || null,
  }
}

async function openCourseView(course) {
  const classroomId = course.classroom?.id
  if (!classroomId) {
    showToast('Classe introuvable', 'error')
    return
  }
  try {
    const { data } = await axios.get(`/api/classrooms/${classroomId}/slides`)
    const slides = data.data ?? data
    if (!slides.length) {
      showToast('Aucune slide dans ce cours', 'error')
      return
    }
    router.visit(`/student/classrooms/${classroomId}/slides/${slides[0].id}`)
  } catch {
    showToast('Impossible de charger les slides', 'error')
  }
}

function contactTeacher(teacher) {
  classroomDetail.value.show = false
  activeView.value = 'messages'
}

function resetProfile() {
  profileForm.value = { name: currentStudent.value.name, email: currentStudent.value.email }
}

async function saveProfile() {
  isLoading.value = true
  try {
    await axios.put('/api/me', { name: profileForm.value.name, email: profileForm.value.email })
    currentStudent.value = { ...currentStudent.value, ...profileForm.value }
    showToast('Profil mis à jour !')
  } catch {
    showToast('Erreur', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  loadStudent()
  await loadMyClassrooms()
  await loadMyCourses()
  const tick = () => {
    currentTime.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  }
  tick()
  setInterval(tick, 60000)
})
</script>
'''

text = text.replace("currentStudent.nom", "currentStudent.name")
text = text.replace("profileForm.nom", "profileForm.name")

text = re.sub(
    r'<button class="logout-btn" @click="logout">',
    '<Link :href="route(\'logout\')" method="post" as="button" class="logout-btn">',
    text,
    count=1,
)
text = text.replace(
    '@click="logout"',
    '',
)
# Close logout as Link
text = re.sub(
    r'(<Link[^>]*class="logout-btn"[^>]*>.*?</svg>)\s*</button>',
    r'\1</Link>',
    text,
    count=1,
    flags=re.DOTALL,
)

text = re.sub(r"<script setup>.*?</script>", script, text, count=1, flags=re.DOTALL)

if ".view-full" not in text:
    text = text.replace(
        ".view { padding:",
        ".view-full { padding: 0; overflow: hidden; height: calc(100vh - 58px); }\n.view-chat { display: flex; flex-direction: column; }\n.view { padding:",
    )

out_path.write_text(text, encoding="utf-8")
print("written", len(text), "to", out_path)
