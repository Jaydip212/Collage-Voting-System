// ============================================================
// COLLEGE VOTING SYSTEM – Frontend Demo (localStorage)
// app.js — Data layer, auth, router shared by all pages
// ============================================================

// ── DEFAULT SEED DATA ─────────────────────────────────────
const SEED = {
  admins: [
    { id:1, name:'Super Admin', email:'admin@college.edu', password:'admin123', role:'admin', photo:'👤' }
  ],
  students: [
    { id:1, name:'Arjun Patil',  rollNo:'BCA-2024-001', email:'arjun@student.edu',   password:'student123', dept:'BCA', deptId:7, year:2, div:'A', gender:'Male',   approved:true  },
    { id:2, name:'Priya Sharma', rollNo:'CS-2024-042',  email:'priya@student.edu',   password:'student123', dept:'CS',  deptId:1, year:3, div:'B', gender:'Female', approved:true  },
    { id:3, name:'Ravi Kumar',   rollNo:'IT-2024-015',  email:'ravi@student.edu',    password:'student123', dept:'IT',  deptId:2, year:2, div:'A', gender:'Male',   approved:false }
  ],
  teachers: [
    { id:1, name:'Prof. Kavita Desai', teacherId:'TCH-CS-001', email:'kavita@college.edu', password:'teacher123', dept:'CS', deptId:1, desig:'Assistant Professor', approved:true },
    { id:2, name:'Prof. Nitin Joshi',  teacherId:'TCH-IT-001', email:'nitin@college.edu',  password:'teacher123', dept:'IT', deptId:2, desig:'Associate Professor', approved:true  }
  ],
  hods: [
    { id:1, name:'Dr. Rajesh Sharma', hodId:'HOD-CS-001', email:'hod.cs@college.edu', password:'hod123', dept:'CS', deptId:1 },
    { id:2, name:'Dr. Meena Nair',    hodId:'HOD-BCA-001',email:'hod.bca@college.edu',password:'hod123', dept:'BCA',deptId:7 }
  ],
  departments: [
    {id:1,name:'Computer Science',code:'CS'},
    {id:2,name:'Information Technology',code:'IT'},
    {id:3,name:'Civil Engineering',code:'CE'},
    {id:4,name:'Mechanical Engineering',code:'ME'},
    {id:5,name:'Electronics & Telecom',code:'ET'},
    {id:6,name:'MBA',code:'MBA'},
    {id:7,name:'BCA',code:'BCA'},
    {id:8,name:'MCA',code:'MCA'}
  ],
  elections: [
    { id:1, title:'BCA CR Election 2026', type:'cr', dept:'BCA', deptId:7,
      desc:'Election for Class Representative of BCA Department',
      status:'active', start:'2026-04-06', end:'2026-04-10',
      candidates:[
        {id:1,name:'Arjun Patil',    dept:'BCA',year:'2nd Year',type:'student'},
        {id:2,name:'Sneha Kulkarni', dept:'BCA',year:'2nd Year',type:'student'},
        {id:3,name:'Rohit Sawant',   dept:'BCA',year:'2nd Year',type:'student'}
      ]
    },
    { id:2, title:'Cultural Secretary Election 2026', type:'cultural', dept:'All', deptId:null,
      desc:'College-wide Cultural Secretary Election',
      status:'upcoming', start:'2026-04-15', end:'2026-04-18',
      candidates:[
        {id:4,name:'Neha Patil',   dept:'CS', year:'3rd Year',type:'student'},
        {id:5,name:'Karan Mehta',  dept:'IT', year:'3rd Year',type:'student'},
        {id:6,name:'Anjali Singh', dept:'BCA',year:'2nd Year',type:'student'}
      ]
    },
    { id:3, title:'CS Teacher Representative', type:'teacher', dept:'CS', deptId:1,
      desc:'Teacher representative election for Computer Science Department',
      status:'completed', start:'2026-04-01', end:'2026-04-04',
      candidates:[
        {id:7,name:'Prof. Kavita Desai',dept:'CS',desig:'Asst. Professor',type:'teacher'},
        {id:8,name:'Prof. Nitin Joshi', dept:'CS',desig:'Assoc. Professor',type:'teacher'}
      ]
    },
    { id:4, title:'Sports Captain Election 2026', type:'sports', dept:'All', deptId:null,
      desc:'Annual Sports Captain election for inter-college games',
      status:'active', start:'2026-04-05', end:'2026-04-11',
      candidates:[
        {id:9, name:'Vikram Rao',   dept:'ME',year:'4th Year',type:'student'},
        {id:10,name:'Sahil Pawar',  dept:'CS',year:'3rd Year',type:'student'},
        {id:11,name:'Tejas Jadhav', dept:'IT',year:'3rd Year',type:'student'}
      ]
    }
  ],
  votes: { 1:{1:45,2:38,3:29}, 2:{}, 3:{7:28,8:17}, 4:{9:12,10:9,11:7} },
  announcements:[
    {id:1,title:'Welcome to College Voting System',body:'The new digital voting system is now live! All students and teachers can register and participate in elections.',date:'2026-04-01',icon:'🗳️',color:'rgba(79,70,229,.2)'},
    {id:2,title:'Election Schedule Released',body:'The election schedule for 2026 is live. BCA CR Election is now ACTIVE — eligible students can vote now!',date:'2026-04-06',icon:'📅',color:'rgba(6,182,212,.2)'},
    {id:3,title:'OTP Demo Mode Active',body:'Since this is a demo, OTP is shown on screen. In production, real emails will be sent via SMTP.',date:'2026-04-07',icon:'🔔',color:'rgba(245,158,11,.2)'}
  ]
};

// ── INIT ──────────────────────────────────────────────────
function initData() {
  if (!localStorage.getItem('cvs_init')) {
    localStorage.setItem('cvs_admins',       JSON.stringify(SEED.admins));
    localStorage.setItem('cvs_students',     JSON.stringify(SEED.students));
    localStorage.setItem('cvs_teachers',     JSON.stringify(SEED.teachers));
    localStorage.setItem('cvs_hods',         JSON.stringify(SEED.hods));
    localStorage.setItem('cvs_departments',  JSON.stringify(SEED.departments));
    localStorage.setItem('cvs_elections',    JSON.stringify(SEED.elections));
    localStorage.setItem('cvs_votes',        JSON.stringify(SEED.votes));
    localStorage.setItem('cvs_announcements',JSON.stringify(SEED.announcements));
    localStorage.setItem('cvs_myVotes',      JSON.stringify({}));
    localStorage.setItem('cvs_init','1');
  }
}

// ── DATA HELPERS ──────────────────────────────────────────
const DB = {
  get:  (k)      => JSON.parse(localStorage.getItem('cvs_'+k) || '[]'),
  set:  (k,v)    => localStorage.setItem('cvs_'+k, JSON.stringify(v)),
  getO: (k)      => JSON.parse(localStorage.getItem('cvs_'+k) || '{}'),
};

// ── AUTH ──────────────────────────────────────────────────
function login(role, identifier, password) {
  let users, user = null;
  if (role === 'admin') {
    users = DB.get('admins');
    user = users.find(u => u.email === identifier && u.password === password);
    if (user) return { success:true, user:{...user,role:'admin'} };
    return { error:'Invalid admin credentials.' };
  }
  if (role === 'student') {
    users = DB.get('students');
    user = users.find(u => (u.email===identifier||u.rollNo===identifier) && u.password===password);
    if (!user) return { error:'Invalid credentials.' };
    if (!user.approved) return { error:'Account pending admin approval.' };
    return { success:true, user:{...user,role:'student'} };
  }
  if (role === 'teacher') {
    users = DB.get('teachers');
    user = users.find(u => (u.email===identifier||u.teacherId===identifier) && u.password===password);
    if (!user) return { error:'Invalid credentials.' };
    if (!user.approved) return { error:'Account pending admin approval.' };
    return { success:true, user:{...user,role:'teacher'} };
  }
  if (role === 'hod') {
    users = DB.get('hods');
    user = users.find(u => (u.email===identifier||u.hodId===identifier) && u.password===password);
    if (!user) return { error:'Invalid HOD credentials.' };
    return { success:true, user:{...user,role:'hod'} };
  }
  return { error:'Unknown role.' };
}

function register(role, data) {
  if (role === 'student') {
    const students = DB.get('students');
    if (students.find(s => s.email===data.email||s.rollNo===data.rollNo))
      return { error:'Email or Roll Number already registered.' };
    const newS = { id: Date.now(), ...data, approved:false, role:'student' };
    students.push(newS);
    DB.set('students', students);
    return { success:true };
  }
  if (role === 'teacher') {
    const teachers = DB.get('teachers');
    if (teachers.find(t => t.email===data.email||t.teacherId===data.teacherId))
      return { error:'Email or Teacher ID already registered.' };
    const newT = { id: Date.now(), ...data, approved:false, role:'teacher' };
    teachers.push(newT);
    DB.set('teachers', teachers);
    return { success:true };
  }
  return { error:'Invalid role.' };
}

function setSession(user) { sessionStorage.setItem('cvs_user', JSON.stringify(user)); }
function getSession()     { return JSON.parse(sessionStorage.getItem('cvs_user')||'null'); }
function logout()         { sessionStorage.removeItem('cvs_user'); window.location.href='login.html'; }

// ── OTP ───────────────────────────────────────────────────
function genOTP() {
  const otp = String(Math.floor(100000+Math.random()*900000));
  sessionStorage.setItem('cvs_otp', otp);
  sessionStorage.setItem('cvs_otp_exp', Date.now()+600000); // 10 min
  return otp;
}
function verifyOTP(code) {
  const stored = sessionStorage.getItem('cvs_otp');
  const exp    = parseInt(sessionStorage.getItem('cvs_otp_exp')||'0');
  if (Date.now() > exp) return false;
  return code === stored;
}

// ── VOTING ────────────────────────────────────────────────
function castVote(electionId, candidateId) {
  const user = getSession();
  if (!user) return { error:'Not logged in.' };
  const myVotes = DB.getO('myVotes');
  if (myVotes[electionId]) return { error:'Already voted in this election.' };
  // Record vote
  const votes = DB.getO('votes');
  if (!votes[electionId]) votes[electionId] = {};
  votes[electionId][candidateId] = (votes[electionId][candidateId]||0)+1;
  DB.set('votes', votes);
  // Record my vote
  myVotes[electionId] = candidateId;
  DB.set('myVotes', myVotes);
  return { success: true };
}

function hasVoted(electionId) {
  const mv = DB.getO('myVotes');
  return !!mv[electionId];
}

function getVoteCount(electionId) {
  const votes = DB.getO('votes');
  const ev = votes[electionId]||{};
  return Object.values(ev).reduce((a,b)=>a+b,0);
}

function getResults(electionId) {
  const votes  = DB.getO('votes');
  const ev     = votes[electionId]||{};
  const elections = DB.get('elections');
  const election  = elections.find(e=>e.id==electionId);
  if (!election) return [];
  const total = Object.values(ev).reduce((a,b)=>a+b,0)||1;
  return election.candidates
    .map(c=>({ ...c, votes: ev[c.id]||0, pct: Math.round(((ev[c.id]||0)/total)*100) }))
    .sort((a,b)=>b.votes-a.votes);
}

// ── ELECTIONS ─────────────────────────────────────────────
function getElections(statusFilter) {
  const elections = DB.get('elections');
  if (!statusFilter) return elections;
  return elections.filter(e=>e.status===statusFilter);
}

function getElectionById(id) {
  return DB.get('elections').find(e=>e.id==id);
}

// ── TOAST ─────────────────────────────────────────────────
function toast(msg, type='s') {
  const ex = document.querySelector('.toast');
  if (ex) ex.remove();
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.innerHTML = `<i class="fas fa-${type==='s'?'check-circle':'times-circle'}"></i><span>${msg}</span>`;
  document.body.appendChild(el);
  setTimeout(()=>el.style.animation='slideIn .3s ease reverse', 2700);
  setTimeout(()=>el.remove(), 3000);
}

// ── OTP INPUT BOXES ──────────────────────────────────────
function setupOtpBoxes(containerId, hiddenId) {
  const boxes = document.querySelectorAll(`#${containerId} .otp-inp`);
  boxes.forEach((box, i) => {
    box.addEventListener('input', e => {
      e.target.value = e.target.value.replace(/\D/,'');
      if (e.target.value && i < boxes.length-1) boxes[i+1].focus();
      updateHidden();
    });
    box.addEventListener('keydown', e => {
      if (e.key==='Backspace' && !e.target.value && i>0) boxes[i-1].focus();
    });
  });
  function updateHidden() {
    const val = [...boxes].map(b=>b.value).join('');
    if (hiddenId) document.getElementById(hiddenId).value = val;
  }
}

// ── OTP COUNTDOWN ────────────────────────────────────────
function startOtpTimer(elId, secs=60) {
  const el = document.getElementById(elId);
  if (!el) return;
  const t = setInterval(()=>{
    secs--;
    if (secs<=0){ clearInterval(t); el.textContent='EXPIRED'; el.style.color='#ef4444'; return; }
    el.textContent = Math.floor(secs/60)+':'+(secs%60+'').padStart(2,'0');
  },1000);
}

// ── REDIRECT IF NOT LOGGED IN ────────────────────────────
function requireLogin(role) {
  const user = getSession();
  if (!user) { window.location.href='login.html'; return null; }
  if (role && user.role !== role) { window.location.href='login.html'; return null; }
  return user;
}

// ── UTILS ─────────────────────────────────────────────────
function fmtDate(d) {
  return new Date(d).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
}
function initials(name) {
  return name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
}

// Auto-init on load
initData();
