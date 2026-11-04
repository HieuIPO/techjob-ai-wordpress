(() => {
  const rows = Array.from(document.querySelectorAll(".applicant-row"));
  const searchInput = document.querySelector("#candidate-search");
  const filterTabs = Array.from(document.querySelectorAll("[data-filter]"));
  const scheduleTabs = Array.from(document.querySelectorAll("[data-schedule]"));
  const schedulePanel = document.querySelector(".schedule-panel");
  const scheduleItems = Array.from(document.querySelectorAll(".schedule-item"));
  const emptyQueue = document.querySelector(".empty-queue");
  const emptySchedule = document.querySelector(".empty-schedule");
  const visibleCount = document.querySelector("#visible-count");
  const dialog = document.querySelector("#application-dialog");
  const toast = document.querySelector("#toast");
  const statusSelect = document.querySelector("#dialog-status");
  let activeFilter = "all";
  let activeApplication = null;
  let toastTimer = 0;

  const labels = {
    submitted: "Mới nộp",
    reviewing: "Đang xem xét",
    interview: "Phỏng vấn",
    passed: "Đạt",
    rejected: "Từ chối"
  };

  const applicationData = {
    a1: { id: "TJ-1042", name: "Trần Khánh Linh", initials: "TL", job: "Backend Developer · Java", email: "linh.tran@example.test", experience: "2 năm", cv: "Tran-Khanh-Linh-CV.pdf", date: "04/11/2026, 09:42", letter: "Tôi muốn đóng góp kinh nghiệm xây dựng API và tối ưu hệ thống backend vào sản phẩm tuyển dụng của TechJob AI." },
    a2: { id: "TJ-1041", name: "Nguyễn Minh Khoa", initials: "NK", job: "Frontend Engineer · React", email: "khoa.nguyen@example.test", experience: "3 năm", cv: "Nguyen-Minh-Khoa-CV.pdf", date: "04/11/2026, 08:16", letter: "Tôi có kinh nghiệm phát triển giao diện React và quan tâm đến trải nghiệm ứng viên trên nền tảng tuyển dụng." },
    a3: { id: "TJ-1038", name: "Lê Thu An", initials: "LA", job: "Data Analyst · SQL", email: "an.le@example.test", experience: "1,5 năm", cv: "Le-Thu-An-CV.pdf", date: "03/11/2026, 16:51", letter: "Tôi mong muốn dùng kỹ năng SQL và phân tích dữ liệu để giúp đội ngũ hiểu rõ hơn các luồng ứng tuyển." },
    a4: { id: "TJ-1036", name: "Phạm Quốc Bảo", initials: "PB", job: "DevOps Engineer · AWS", email: "bao.pham@example.test", experience: "4 năm", cv: "Pham-Quoc-Bao-CV.pdf", date: "03/11/2026, 14:20", letter: "Tôi đã vận hành hạ tầng AWS cho các sản phẩm web và muốn chia sẻ kinh nghiệm đó trong đội kỹ thuật." },
    a5: { id: "TJ-1033", name: "Nguyễn Hải Yến", initials: "NY", job: "QA Engineer · Automation", email: "yen.nguyen@example.test", experience: "2 năm", cv: "Nguyen-Hai-Yen-CV.pdf", date: "03/11/2026, 11:05", letter: "Tôi tập trung vào tự động hóa kiểm thử và chất lượng phát hành, đặc biệt với các sản phẩm có nhiều luồng người dùng." },
    a6: { id: "TJ-1028", name: "Đỗ Tuấn Kiệt", initials: "DK", job: "PHP Developer · WordPress", email: "kiet.do@example.test", experience: "2,5 năm", cv: "Do-Tuan-Kiet-CV.pdf", date: "02/11/2026, 17:34", letter: "Tôi đã xây dựng plugin WordPress và mong muốn tiếp tục làm việc với các sản phẩm dựa trên nền tảng mã nguồn mở." }
  };

  const pipeline = { submitted: 6, reviewing: 7, interview: 4, passed: 8, rejected: 3 };

  function announce(message) {
    toast.textContent = message;
    toast.classList.add("is-visible");
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove("is-visible"), 3200);
  }

  function isPending(status) {
    return status === "submitted" || status === "reviewing";
  }

  function refreshCounts() {
    const pendingRows = rows.filter(row => isPending(row.dataset.status));
    const counts = {
      all: pendingRows.length,
      submitted: pendingRows.filter(row => row.dataset.status === "submitted").length,
      reviewing: pendingRows.filter(row => row.dataset.status === "reviewing").length
    };

    Object.entries(counts).forEach(([key, value]) => {
      const target = document.querySelector('[data-count="' + key + '"]');
      if (target) target.textContent = String(value);
    });

    document.querySelectorAll('[data-metric="pending"]').forEach(target => {
      target.textContent = String(pipeline.submitted + pipeline.reviewing).padStart(2, "0");
    });

    Object.entries(pipeline).forEach(([key, value]) => {
      const stage = document.querySelector('[data-pipeline="' + key + '"]');
      if (stage) {
        const count = stage.querySelector(".pipeline-stage__label strong");
        const fill = stage.querySelector(".pipeline-fill");
        if (count) count.textContent = String(value).padStart(2, "0");
        if (fill) fill.style.setProperty("--fill", ((value / 28) * 100).toFixed(1) + "%");
      }
    });
  }

  function applyQueueFilter() {
    const query = (searchInput.value || "").trim().toLocaleLowerCase("vi");
    let shown = 0;

    rows.forEach(row => {
      const matchesStatus = activeFilter === "all" || row.dataset.status === activeFilter;
      const haystack = (row.dataset.name + " " + row.dataset.job).toLocaleLowerCase("vi");
      const matchesSearch = !query || haystack.includes(query);
      const visible = isPending(row.dataset.status) && matchesStatus && matchesSearch;
      row.hidden = !visible;
      if (visible) shown += 1;
    });

    visibleCount.textContent = String(shown);
    emptyQueue.hidden = shown !== 0;
    refreshCounts();
  }

  function setStatusTag(row, status) {
    const tag = row.querySelector(".status-tag");
    if (!tag) return;
    tag.className = "status-tag status-tag--" + status;
    tag.innerHTML = "<span></span>" + labels[status];
  }

  function updatePipeline(previous, next) {
    if (previous === next) return;
    if (pipeline[previous] > 0) pipeline[previous] -= 1;
    pipeline[next] += 1;
  }

  function setSchedulePeriod(period) {
    schedulePanel.classList.toggle("is-week-view", period === "week");
    scheduleTabs.forEach(tab => {
      const selected = tab.dataset.schedule === period;
      tab.classList.toggle("is-selected", selected);
      tab.setAttribute("aria-selected", selected ? "true" : "false");
    });

    let shown = 0;
    scheduleItems.forEach(item => {
      const visible = period === "week" || item.dataset.day === "today";
      item.hidden = !visible;
      if (visible) shown += 1;
    });

    emptySchedule.hidden = shown !== 0;
  }

  function openApplication(id) {
    const row = rows.find(item => item.dataset.appId === id);
    const data = applicationData[id];
    if (!row || !data) return;

    activeApplication = { id, row };
    document.querySelector("#dialog-id").textContent = "#" + data.id;
    document.querySelector("#dialog-title").textContent = data.name;
    document.querySelector("#dialog-job").textContent = data.job;
    document.querySelector("#dialog-avatar").textContent = data.initials;
    document.querySelector("#dialog-email").textContent = data.email;
    document.querySelector("#dialog-experience").textContent = data.experience;
    document.querySelector("#dialog-cv").textContent = data.cv;
    document.querySelector("#dialog-date").textContent = data.date;
    document.querySelector("#dialog-letter").textContent = data.letter;
    statusSelect.value = row.dataset.status;
    dialog.showModal();
  }

  filterTabs.forEach(tab => {
    tab.addEventListener("click", () => {
      activeFilter = tab.dataset.filter;
      filterTabs.forEach(item => {
        const selected = item === tab;
        item.classList.toggle("is-selected", selected);
        item.setAttribute("aria-selected", selected ? "true" : "false");
      });
      applyQueueFilter();
    });
  });

  searchInput.addEventListener("input", applyQueueFilter);

  document.addEventListener("click", event => {
    const openButton = event.target.closest("[data-open-app]");
    if (openButton) openApplication(openButton.dataset.openApp);

    const periodButton = event.target.closest("[data-schedule]");
    if (periodButton) setSchedulePeriod(periodButton.dataset.schedule);

    const scrollButton = event.target.closest("[data-scroll-to]");
    if (scrollButton) {
      event.preventDefault();
      document.querySelector(scrollButton.dataset.scrollTo)?.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    const demoLink = event.target.closest("[data-demo-link]");
    if (demoLink) {
      const target = document.querySelector(demoLink.getAttribute("href"));
      if (target) {
        event.preventDefault();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      } else {
        event.preventDefault();
        announce("Mục này chỉ được minh họa trong prototype, chưa kết nối WordPress.");
      }
    }

    const demoAction = event.target.closest("[data-demo-action]");
    if (demoAction) announce(demoAction.dataset.demoAction + " chỉ được mô phỏng trong prototype.");
  });

  document.querySelector("#save-demo-status").addEventListener("click", () => {
    if (!activeApplication) return;
    const nextStatus = statusSelect.value;
    const previousStatus = activeApplication.row.dataset.status;
    updatePipeline(previousStatus, nextStatus);
    activeApplication.row.dataset.status = nextStatus;
    setStatusTag(activeApplication.row, nextStatus);
    applyQueueFilter();
    dialog.close();
    activeApplication = null;
    announce("Đã đổi trạng thái trong bản nháp. WordPress không được cập nhật.");
  });

  document.querySelector("[data-close-dialog]").addEventListener("click", () => dialog.close());

  const menuButton = document.querySelector(".mobile-menu-button");
  const sidebar = document.querySelector("#sidebar");
  const scrim = document.querySelector(".sidebar-scrim");

  function setMenuOpen(open) {
    sidebar.classList.toggle("is-open", open);
    menuButton.setAttribute("aria-expanded", open ? "true" : "false");
    scrim.hidden = !open;
  }

  menuButton.addEventListener("click", () => setMenuOpen(!sidebar.classList.contains("is-open")));
  scrim.addEventListener("click", () => setMenuOpen(false));
  sidebar.addEventListener("click", event => {
    if (event.target.closest('a[href="#applications"], a[href="#schedule"], a[href="#dashboard"]')) setMenuOpen(false);
  });

  document.addEventListener("keydown", event => {
    if (event.key === "/" && !event.ctrlKey && !event.metaKey && !["INPUT", "TEXTAREA", "SELECT"].includes(document.activeElement.tagName)) {
      event.preventDefault();
      searchInput.focus();
    }
  });

  const pipelineValues = document.querySelectorAll(".pipeline-stage__label strong");
  const pipelineRows = ["submitted", "reviewing", "interview", "passed", "rejected"];
  pipelineRows.forEach((key, index) => {
    const stage = pipelineValues[index]?.closest(".pipeline-stage");
    if (stage) stage.dataset.pipeline = key;
  });

  applyQueueFilter();
  setSchedulePeriod("today");
})();
