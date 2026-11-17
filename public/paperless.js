const $=s=>document.querySelector(s);
function syncTheme(){const dark=document.documentElement.dataset.theme==='dark';document.querySelectorAll('#themeToggle,#profileTheme').forEach(button=>{const label=button.querySelectorAll('span')[1];if(label)label.textContent=dark?'Mode terang':'Mode gelap';button.setAttribute('aria-pressed',String(dark));});}
function toggleTheme(){const next=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=next;try{localStorage.setItem('paperless-theme',next)}catch{}syncTheme();}
document.addEventListener('click',event=>{if(event.target.closest('#themeToggle,#profileTheme'))toggleTheme();if(event.target.closest('#menuButton')){$('#sidebar')?.classList.add('open');$('#scrim')?.classList.add('open')}if(event.target.closest('#scrim')){$('#sidebar')?.classList.remove('open');$('#scrim')?.classList.remove('open')}if(event.target.closest('#showPassword')){const input=$('#password');if(input){input.type=input.type==='password'?'text':'password';event.target.textContent=input.type==='password'?'Tampilkan':'Sembunyikan'}}});
document.addEventListener('DOMContentLoaded',()=>{
    syncTheme();
    const type=$('#type'),container=$('#dynamic-fields');
    if(type&&container){
        const values=JSON.parse(container.dataset.values||'{}');
        const definitions={
            active:[['full_name','Nama lengkap','text'],['nim','NIM','text'],['program','Program studi','text'],['semester','Semester','number']],
            event:[['event_name','Nama kegiatan','text'],['organizer','Penyelenggara','text'],['event_date','Tanggal kegiatan','date'],['location','Lokasi kegiatan','text'],['responsible','Penanggung jawab','text']],
            facility:[['facility','Fasilitas yang dipinjam','text'],['event_name','Nama kegiatan','text'],['event_date','Tanggal penggunaan','date'],['start_time','Waktu mulai','time'],['end_time','Waktu selesai','time'],['participants','Jumlah peserta','number'],['contact','Nomor kontak','text']],
        };
        const renderFields=()=>{container.innerHTML=(definitions[type.value]||[]).map(([name,label,inputType])=>`<div class="field"><label for="field-${name}">${label}</label><input id="field-${name}" name="${name}" type="${inputType}" value="${String(values[name]??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]))}" required></div>`).join('')};
        type.addEventListener('change',renderFields);renderFields();
    }
});
