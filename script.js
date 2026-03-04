const site = window.SITE_CONTENT || {};

const pages = Array.from(document.querySelectorAll(".story-page"));
const pagePrevBtn = document.getElementById("pagePrevBtn");
const pageNextBtn = document.getElementById("pageNextBtn");
const pageIndicatorCurrent = document.getElementById("pageIndicatorCurrent");
const pageIndicatorTotal = document.getElementById("pageIndicatorTotal");
const countdownEl = document.getElementById("countdown");

const gallerySlides = document.querySelectorAll(".gallery-slide");
const galleryDots = document.querySelectorAll(".gallery-dot");
const prevSlideBtn = document.getElementById("prevSlideBtn");
const nextSlideBtn = document.getElementById("nextSlideBtn");

const confettiCanvas = document.getElementById("confettiCanvas");
const videoMuteBtn = document.getElementById("videoMuteBtn");
const loveFloatLayer = document.getElementById("loveFloatLayer");

let currentPage = 0;
let currentSlide = 0;
let galleryTimer;
let songAudio;
let confettiFrame;
let isBackgroundMuted = false;
let loveFloatTimer;
let confettiRunning = false;

function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

function updateCountdown() {
    if (!countdownEl) {
        return;
    }

    const anniversaryDate = site.anniversaryDate || "2026-03-03T00:00:00";
    const target = new Date(anniversaryDate).getTime();
    const now = Date.now();
    let delta = now - target;

    if (delta < 0) {
        delta = Math.abs(delta);
        const days = Math.floor(delta / (1000 * 60 * 60 * 24));
        countdownEl.textContent = `${days} day(s) until ${new Date(anniversaryDate).toDateString()}`;
        return;
    }

    const days = Math.floor(delta / (1000 * 60 * 60 * 24));
    const hours = Math.floor((delta / (1000 * 60 * 60)) % 24);
    const mins = Math.floor((delta / (1000 * 60)) % 60);
    countdownEl.textContent = `${days} days, ${hours} hours, ${mins} minutes`;
}

function updatePageUI() {
    pages.forEach((page, index) => {
        page.classList.toggle("is-active", index === currentPage);
    });

    pageIndicatorCurrent.textContent = String(currentPage + 1);
    pageIndicatorTotal.textContent = String(pages.length);
    pagePrevBtn.disabled = currentPage === 0;
    pageNextBtn.disabled = currentPage === pages.length - 1;

    if (currentPage === pages.length - 1) {
        startConfetti();
    } else {
        stopConfetti();
    }

    if (currentPage > 0) {
        startLoveFloat();
    } else {
        stopLoveFloat();
    }
}

function setPage(index) {
    currentPage = clamp(index, 0, pages.length - 1);
    updatePageUI();
}

function nextPage() {
    setPage(currentPage + 1);
}

function prevPage() {
    setPage(currentPage - 1);
}

function setSlide(index) {
    if (!gallerySlides.length) {
        return;
    }

    const safeIndex = (index + gallerySlides.length) % gallerySlides.length;
    currentSlide = safeIndex;

    gallerySlides.forEach((slide, slideIndex) => {
        slide.classList.toggle("is-active", slideIndex === safeIndex);
    });

    galleryDots.forEach((dot, dotIndex) => {
        dot.classList.toggle("is-active", dotIndex === safeIndex);
    });
}

function startGalleryAutoplay() {
    if (gallerySlides.length < 2) {
        return;
    }

    clearInterval(galleryTimer);
    galleryTimer = setInterval(() => {
        setSlide(currentSlide + 1);
    }, 3500);
}

function initMusic() {
    if (!site.musicFile) {
        return;
    }

    songAudio = new Audio(site.musicFile);
    songAudio.loop = true;
    songAudio.volume = clamp(Number(site.musicVolume ?? 0.45), 0, 1);

    const tryPlay = () => {
        if (isBackgroundMuted) {
            return;
        }
        songAudio.play().then(() => {
            cleanupAutoplayFallback();
        }).catch(() => {
            // Browser blocked autoplay; fallback waits for a user gesture.
        });
    };

    const fallbackPlay = () => tryPlay();

    function cleanupAutoplayFallback() {
        document.removeEventListener("click", fallbackPlay);
        document.removeEventListener("touchstart", fallbackPlay);
        document.removeEventListener("keydown", fallbackPlay);
    }

    document.addEventListener("click", fallbackPlay);
    document.addEventListener("touchstart", fallbackPlay);
    document.addEventListener("keydown", fallbackPlay);
    tryPlay();
}

function updateVideoMuteButton() {
    if (!videoMuteBtn) {
        return;
    }
    videoMuteBtn.textContent = isBackgroundMuted
        ? "Unmute Background Music"
        : "Mute Background Music";
}

function toggleBackgroundMusic() {
    isBackgroundMuted = !isBackgroundMuted;

    if (!songAudio) {
        updateVideoMuteButton();
        return;
    }

    if (isBackgroundMuted) {
        songAudio.pause();
    } else {
        songAudio.play().catch(() => {
            // If blocked, next user gesture can trigger it.
        });
    }

    updateVideoMuteButton();
}

function startConfetti() {
    if (!confettiCanvas || confettiRunning) {
        return;
    }

    const ctx = confettiCanvas.getContext("2d");
    confettiCanvas.width = confettiCanvas.offsetWidth;
    confettiCanvas.height = confettiCanvas.offsetHeight;
    confettiRunning = true;

    const particles = Array.from({ length: 120 }, () => ({
        x: Math.random() * confettiCanvas.width,
        y: -20 - Math.random() * confettiCanvas.height,
        speed: 0.35 + Math.random() * 1.15,
        size: 5 + Math.random() * 6,
        color: ["#ff5d9e", "#ff7fb5", "#ff99c8", "#ffbfdc"][Math.floor(Math.random() * 4)],
        drift: -0.35 + Math.random() * 0.7
    }));

    cancelAnimationFrame(confettiFrame);

    function draw() {
        if (!confettiRunning) {
            return;
        }
        ctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
        particles.forEach((p) => {
            p.y += p.speed;
            p.x += p.drift;

            if (p.y > confettiCanvas.height + 10) {
                p.y = -10;
                p.x = Math.random() * confettiCanvas.width;
            }

            ctx.fillStyle = p.color;
            ctx.fillRect(p.x, p.y, p.size, p.size * 0.65);
        });

        confettiFrame = requestAnimationFrame(draw);
    }

    draw();
}

function stopConfetti() {
    confettiRunning = false;
    cancelAnimationFrame(confettiFrame);
    if (!confettiCanvas) {
        return;
    }
    const ctx = confettiCanvas.getContext("2d");
    ctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
}

function spawnLoveFloat() {
    if (!loveFloatLayer) {
        return;
    }

    const emojis = ["\u2764\uFE0F", "\u{1F495}", "\u{1F60D}", "\u{1F618}", "\u{1F970}"];
    const item = document.createElement("span");
    item.className = "love-float-item";
    item.textContent = emojis[Math.floor(Math.random() * emojis.length)];
    item.style.left = `${Math.random() * 100}%`;
    item.style.animationDuration = `${4.8 + Math.random() * 3.2}s`;
    item.style.fontSize = `${18 + Math.random() * 16}px`;
    item.style.opacity = `${0.55 + Math.random() * 0.4}`;
    item.style.transform = `translateX(${(-16 + Math.random() * 32).toFixed(1)}px)`;

    item.addEventListener("animationend", () => {
        item.remove();
    });

    loveFloatLayer.appendChild(item);
}

function startLoveFloat() {
    if (!loveFloatLayer || loveFloatTimer) {
        return;
    }

    for (let i = 0; i < 8; i += 1) {
        spawnLoveFloat();
    }

    loveFloatTimer = setInterval(spawnLoveFloat, 420);
}

function stopLoveFloat() {
    if (loveFloatTimer) {
        clearInterval(loveFloatTimer);
        loveFloatTimer = null;
    }

    if (!loveFloatLayer) {
        return;
    }

    loveFloatLayer.innerHTML = "";
}

pagePrevBtn.addEventListener("click", prevPage);
pageNextBtn.addEventListener("click", nextPage);

document.addEventListener("keydown", (event) => {
    if (event.key === "ArrowRight") {
        nextPage();
    } else if (event.key === "ArrowLeft") {
        prevPage();
    }
});

if (prevSlideBtn && nextSlideBtn) {
    prevSlideBtn.addEventListener("click", () => {
        setSlide(currentSlide - 1);
        startGalleryAutoplay();
    });

    nextSlideBtn.addEventListener("click", () => {
        setSlide(currentSlide + 1);
        startGalleryAutoplay();
    });
}

galleryDots.forEach((dot) => {
    dot.addEventListener("click", () => {
        const dotIndex = Number(dot.dataset.dotIndex || "0");
        setSlide(dotIndex);
        startGalleryAutoplay();
    });
});

if (videoMuteBtn) {
    videoMuteBtn.addEventListener("click", toggleBackgroundMusic);
}

setPage(0);
setSlide(0);
startGalleryAutoplay();
updateCountdown();
setInterval(updateCountdown, 1000 * 60);
initMusic();
updateVideoMuteButton();
