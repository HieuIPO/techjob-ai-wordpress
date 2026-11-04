(() => {
  'use strict';
  // Fictional companies and jobs: prototype state only, never sent to WordPress.
  const jobs = [
    { id: 1, title: 'Frontend Developer (React, TypeScript)', company: 'Mekong Labs', mark: 'ml', theme: '', location: 'Hồ Chí Minh', work: 'Hybrid', level: 'Middle', min: 25, max: 40, tags: ['React', 'TypeScript', 'Next.js'], age: 'Mới đăng', description: 'Cùng đội ngũ sản phẩm xây dựng giao diện web dễ sử dụng. Bạn sẽ phát triển các tính năng bằng React, cải thiện hiệu năng và cộng tác với nhóm thiết kế.' },
    { id: 2, title: 'Java Backend Developer', company: 'An Nam Digital', mark: 'an', theme: 'nam', location: 'Hà Nội', work: 'Tại văn phòng', level: 'Junior', min: 18, max: 28, tags: ['Java', 'Spring Boot', 'SQL'], age: 'Minh họa', description: 'Phát triển API và các dịch vụ backend bằng Java. Tham gia thiết kế dữ liệu, viết kiểm thử và cải thiện chất lượng hệ thống.' },
    { id: 3, title: 'Senior Python Engineer', company: 'Sông Hàn Studio', mark: 'sh', theme: 'song', location: 'Làm việc từ xa', work: 'Remote', level: 'Senior', min: 40, max: 60, tags: ['Python', 'FastAPI', 'Docker'], age: 'Minh họa', description: 'Thiết kế dịch vụ xử lý dữ liệu và API bằng Python. Chia sẻ kinh nghiệm kỹ thuật, đánh giá giải pháp và hỗ trợ đồng đội.' },
    { id: 4, title: 'Software Tester Intern', company: 'Mekong Labs', mark: 'ml', theme: '', location: 'Đà Nẵng', work: 'Tại văn phòng', level: 'Intern', min: 5, max: 8, tags: ['Tester', 'Manual Test', 'SQL'], age: 'Minh họa', description: 'Làm quen với quy trình kiểm thử phần mềm, viết test case và phối hợp với đội phát triển để ghi nhận lỗi rõ ràng.' }
  ];
  const $ = selector => document.querySelector(selector);
  const saved = new Set(); let savedOnly = false; let toastTimer;
  const toast = text => { $('#toast').textContent = text; $('#toast').hidden = false; clearTimeout(toastTimer); toastTimer = setTimeout(() => { $('#toast').hidden = true; }, 3500); };
  const card = job => `<article class="job-card"><button class="save-button" data-save="${job.id}" aria-label="${saved.has(job.id) ? 'Bỏ lưu' : 'Lưu'} ${job.title}" aria-pressed="${saved.has(job.id)}">${saved.has(job.id) ? '▣' : '▢'}</button><div class="job-top"><span class="company-logo ${job.theme}" aria-hidden="true">${job.mark}</span><div><h3><button class="job-title" data-detail="${job.id}">${job.title}</button></h3><p class="company-name">${job.company}</p></div></div><div class="job-meta"><span class="salary">${job.min} - ${job.max} triệu</span><span class="meta-separator" aria-hidden="true"></span><span>${job.location}</span><span>${job.work}</span></div><div class="job-bottom"><div class="tags">${[...job.tags, job.level].map(tag => `<span class="tag">${tag}</span>`).join('')}</div><span class="job-age">${job.age}</span></div></article>`;
  function render() {
    const q = $('#keyword').value.trim().toLocaleLowerCase('vi'); const location = $('#location').value;
    const levels = [...document.querySelectorAll('[name="level"]:checked')].map(input => input.value);
    const filtered = jobs.filter(job => (!q || `${job.title} ${job.company} ${job.tags.join(' ')}`.toLocaleLowerCase('vi').includes(q)) && (!location || job.location === location) && (!levels.length || levels.includes(job.level)) && ((!$('[name="remote"]').checked && !$('[name="hybrid"]').checked) || ($('[name="remote"]').checked && job.work === 'Remote') || ($('[name="hybrid"]').checked && job.work === 'Hybrid')) && job.min >= Number($('#salary').value) && (!savedOnly || saved.has(job.id)));
    if ($('#sort').value === 'salary') filtered.sort((a, b) => b.max - a.max);
    $('#job-list').innerHTML = filtered.map(card).join(''); $('#empty').hidden = !!filtered.length;
    $('#result-count').textContent = `${filtered.length} vị trí minh họa${savedOnly ? ' đã lưu' : ' phù hợp'}`;
    $('.saved-count').textContent = saved.size; $('#results-title').textContent = savedOnly ? 'Việc làm đã lưu' : 'Việc làm dành cho bạn';
  }
  function reset() { $('#search-form').reset(); document.querySelectorAll('.filters input').forEach(input => { input.checked = false; }); $('#salary').value = '0'; savedOnly = false; render(); }
  $('#search-form').addEventListener('submit', event => { event.preventDefault(); savedOnly = false; render(); $('#jobs').scrollIntoView({block:'start',behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'}); });
  document.querySelectorAll('.filters input,.filters select,#sort').forEach(input => input.addEventListener('change', render));
  $('#clear').addEventListener('click', reset); $('#clear-empty').addEventListener('click', reset);
  document.querySelectorAll('[data-keyword]').forEach(button => button.addEventListener('click', () => { $('#keyword').value = button.dataset.keyword; savedOnly = false; render(); }));
  $('[data-saved]').addEventListener('click', () => { savedOnly = !savedOnly; render(); });
  $('#job-list').addEventListener('click', event => {
    const save = event.target.closest('[data-save]'); const detail = event.target.closest('[data-detail]');
    if (save) { const id = Number(save.dataset.save); saved.has(id) ? saved.delete(id) : saved.add(id); render(); const next = document.querySelector(`[data-save="${id}"]`); next?.focus(); toast(saved.has(id) ? 'Đã lưu vào danh sách của bạn.' : 'Đã bỏ lưu vị trí.'); }
    if (detail) { const job = jobs.find(item => item.id === Number(detail.dataset.detail)); $('#detail-content').innerHTML = `<h2 id="detail-title">${job.title}</h2><p class="muted">${job.company} / ${job.location}</p><h3>Mô tả công việc</h3><p>${job.description}</p><h3>Kỹ năng</h3><div class="tags">${job.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}</div><h3>Mức lương minh họa</h3><p class="salary">${job.min} - ${job.max} triệu / tháng</p>`; $('#detail-dialog').showModal(); }
  });
  document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
  document.querySelectorAll('[data-account]').forEach(button => button.addEventListener('click', () => toast('Bản nháp giao diện. Đăng nhập và tạo hồ sơ sẽ dùng hệ thống WordPress hiện có.')));
  $('#practice-open').addEventListener('click', () => $('#practice-dialog').showModal());
  $('#practice-skill').addEventListener('change', event => { $('#question-text').textContent = { Java:'Trong Java, bạn phân biệt interface và abstract class như thế nào?', React:'Khi nào bạn dùng state và khi nào dùng ref trong React?', Python:'Generator trong Python hoạt động như thế nào? Hãy nêu một trường hợp sử dụng.' }[event.target.value]; });
  $('.menu-toggle').addEventListener('click', event => { const open = $('#navigation').classList.toggle('open'); event.currentTarget.setAttribute('aria-expanded', String(open)); });
  render();
})();
