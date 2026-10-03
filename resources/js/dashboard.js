document.addEventListener("DOMContentLoaded", () => {
initSidebar();
initCanvaStatus();
initSyncForm();
});

/* ==========================================
SIDEBAR
========================================== */

function initSidebar() {
const sidebar = document.getElementById("sidebar");
const toggle = document.getElementById("sidebarToggle");
const overlay = document.getElementById("sidebarOverlay");


if (!sidebar) {
    return;
}

const storageKey = "gps-sidebar-collapsed";

// Restore desktop sidebar state
try {
    const saved = localStorage.getItem(storageKey);

    if (saved === "true" && window.innerWidth > 991) {
        sidebar.classList.add("collapsed");
        document.body.classList.add("sidebar-collapsed");
    }
} catch (error) {
    console.warn(
        "Sidebar state tidak dapat dibaca:",
        error
    );
}

// Toggle sidebar
if (toggle) {
    toggle.addEventListener("click", () => {
        if (window.innerWidth <= 991) {
            sidebar.classList.toggle("mobile-open");

            if (overlay) {
                overlay.classList.toggle("active");
            }

            document.body.classList.toggle(
                "sidebar-open"
            );

            return;
        }

        sidebar.classList.toggle("collapsed");

        document.body.classList.toggle(
            "sidebar-collapsed",
            sidebar.classList.contains("collapsed")
        );

        try {
            localStorage.setItem(
                storageKey,
                sidebar.classList.contains("collapsed")
            );
        } catch (error) {
            console.warn(
                "Sidebar state tidak dapat disimpan:",
                error
            );
        }
    });
}

// Close mobile sidebar
if (overlay) {
    overlay.addEventListener(
        "click",
        closeMobileSidebar
    );
}

// Close sidebar dengan tombol ESC
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeMobileSidebar();
    }
});

// Responsive handling
window.addEventListener("resize", () => {
    if (window.innerWidth > 991) {
        sidebar.classList.remove("mobile-open");

        if (overlay) {
            overlay.classList.remove("active");
        }

        document.body.classList.remove(
            "sidebar-open"
        );
    }
});

function closeMobileSidebar() {
    sidebar.classList.remove("mobile-open");

    if (overlay) {
        overlay.classList.remove("active");
    }

    document.body.classList.remove(
        "sidebar-open"
    );
}


}

/* ==========================================
CANVA CONNECTION STATUS
========================================== */

function initCanvaStatus() {
checkCanvaStatus();


setInterval(() => {
    checkCanvaStatus();
}, 30000);


}

/* ==========================================
CHECK CANVA STATUS
========================================== */

async function checkCanvaStatus() {
const navbarText =
document.getElementById("canvaStatusText");


const navbarDot =
    document.getElementById("canvaStatusDot");

const sidebarText =
    document.getElementById("sidebarCanvaStatus");

const sidebarDot =
    document.getElementById("sidebarCanvaDot");

if (
    !navbarText &&
    !navbarDot &&
    !sidebarText &&
    !sidebarDot
) {
    return;
}

setCanvaCheckingState(
    navbarText,
    navbarDot,
    sidebarText,
    sidebarDot
);

try {
    const response = await fetch(
        "/canva/status",
        {
            method: "GET",

            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },

            credentials: "same-origin",
            cache: "no-store",
        }
    );

    const responseText =
        await response.text();

    let result = null;

    // Parse JSON
    if (
        responseText &&
        responseText.trim() !== ""
    ) {
        try {
            result = JSON.parse(
                responseText
            );
        } catch (jsonError) {
            console.error(
                "Response Canva status bukan JSON:",
                responseText
            );

            throw new Error(
                "Response status Canva tidak valid."
            );
        }
    }

    // HTTP error
    if (!response.ok) {
        throw new Error(
            result?.message ||
                `Gagal memeriksa Canva. HTTP ${response.status}`
        );
    }

    console.log(
        "Canva Status Response:",
        result
    );

    // Deteksi status Canva
    const connected =
        result?.connected === true ||
        result?.is_connected === true ||
        result?.authenticated === true ||
        result?.has_token === true ||
        result?.token_exists === true ||
        result?.status === "connected" ||
        result?.status === "authenticated" ||
        result?.status === "success";

    // Connected
    if (connected) {
        setCanvaConnectedState(
            navbarText,
            navbarDot,
            sidebarText,
            sidebarDot
        );

        updateCanvaConnectionButton(true);

        return;
    }

    // Not connected
    setCanvaDisconnectedState(
        navbarText,
        navbarDot,
        sidebarText,
        sidebarDot
    );

    updateCanvaConnectionButton(false);
} catch (error) {
    console.error(
        "Canva Status Error:",
        error
    );

    setCanvaDisconnectedState(
        navbarText,
        navbarDot,
        sidebarText,
        sidebarDot
    );

    updateCanvaConnectionButton(false);
}


}

/* ==========================================
CANVA CHECKING STATE
========================================== */

function setCanvaCheckingState(
navbarText,
navbarDot,
sidebarText,
sidebarDot
) {
if (navbarText) {
navbarText.textContent =
"Memeriksa Canva...";
}


if (navbarDot) {
    navbarDot.classList.remove(
        "connected",
        "disconnected"
    );

    navbarDot.classList.add(
        "checking"
    );
}

if (sidebarText) {
    sidebarText.textContent =
        "Checking";
}

if (sidebarDot) {
    sidebarDot.classList.remove(
        "connected",
        "disconnected"
    );

    sidebarDot.classList.add(
        "checking"
    );
}


}

/* ==========================================
CANVA CONNECTED STATE
========================================== */

function setCanvaConnectedState(
navbarText,
navbarDot,
sidebarText,
sidebarDot
) {
if (navbarText) {
navbarText.textContent =
"Canva Terhubung";
}


if (navbarDot) {
    navbarDot.classList.remove(
        "checking",
        "disconnected"
    );

    navbarDot.classList.add(
        "connected"
    );
}

if (sidebarText) {
    sidebarText.textContent =
        "Terhubung";
}

if (sidebarDot) {
    sidebarDot.classList.remove(
        "checking",
        "disconnected"
    );

    sidebarDot.classList.add(
        "connected"
    );
}


}

/* ==========================================
CANVA DISCONNECTED STATE
========================================== */

function setCanvaDisconnectedState(
navbarText,
navbarDot,
sidebarText,
sidebarDot
) {
if (navbarText) {
navbarText.textContent =
"Canva Belum Terhubung";
}


if (navbarDot) {
    navbarDot.classList.remove(
        "checking",
        "connected"
    );

    navbarDot.classList.add(
        "disconnected"
    );
}

if (sidebarText) {
    sidebarText.textContent =
        "Not Connected";
}

if (sidebarDot) {
    sidebarDot.classList.remove(
        "checking",
        "connected"
    );

    sidebarDot.classList.add(
        "disconnected"
    );
}


}

/* ==========================================
CANVA CONNECTION BUTTON
========================================== */

function updateCanvaConnectionButton(
connected
) {
const buttons =
document.querySelectorAll(
'a[href*="/canva/connect"], button[data-canva-connect]'
);


buttons.forEach((button) => {
    if (connected) {
        button.classList.add(
            "canva-connected"
        );

        const textElement =
            button.querySelector(
                ".button-text"
            );

        if (textElement) {
            textElement.textContent =
                "✓ Canva Terhubung";

            return;
        }

        const currentText =
            button.textContent.trim();

        if (
            currentText === "Hubungkan Canva" ||
            currentText === "CONNECT CANVA" ||
            currentText === "Connect Canva"
        ) {
            button.innerHTML =
                "<span>✓ Canva Terhubung</span>";
        }
    } else {
        button.classList.remove(
            "canva-connected"
        );
    }
});


}

/* ==========================================
SYNC GOOGLE SHEETS → CANVA
========================================== */

function initSyncForm() {
const form =
document.getElementById("syncForm");


if (!form) {
    console.warn(
        "Form sync #syncForm tidak ditemukan."
    );

    return;
}

const button =
    document.getElementById("syncButton") ||
    form.querySelector(
        'button[type="submit"]'
    );

let isSyncing = false;

form.addEventListener(
    "submit",
    async (event) => {
        event.preventDefault();

        // Prevent double click
        if (isSyncing) {
            return;
        }

        isSyncing = true;

        setButtonLoading(
            button,
            true
        );

        try {
            const csrfToken =
                document
                    .querySelector(
                        'meta[name="csrf-token"]'
                    )
                    ?.getAttribute("content") || "";

            const formData =
                new FormData(form);

            const response =
                await fetch(
                    form.action,
                    {
                        method: "POST",

                        headers: {
                            "X-CSRF-TOKEN":
                                csrfToken,

                            Accept:
                                "application/json",

                            "X-Requested-With":
                                "XMLHttpRequest",
                        },

                        body: formData,

                        credentials:
                            "same-origin",

                        redirect: "manual",
                    }
                );

            const responseText =
                await response.text();

            let result = null;

            // Parse response JSON
            if (
                responseText.trim() !== ""
            ) {
                try {
                    result =
                        JSON.parse(
                            responseText
                        );
                } catch (jsonError) {
                    console.error(
                        "Response bukan JSON valid:",
                        responseText
                    );

                    if (response.ok) {
                        showSyncSuccess(
                            "Sinkronisasi berhasil diproses."
                        );

                        setTimeout(
                            () => {
                                window.location.reload();
                            },
                            1000
                        );

                        return;
                    }

                    throw new Error(
                        `Server mengembalikan response yang tidak valid (${response.status}).`
                    );
                }
            }

            // HTTP error
            if (!response.ok) {
                const message =
                    result?.message ||
                    `Sinkronisasi gagal. HTTP ${response.status}`;

                throw new Error(
                    message
                );
            }

            // Backend response kosong
            if (!result) {
                showSyncSuccess(
                    "Permintaan sinkronisasi berhasil dikirim."
                );

                setTimeout(
                    () => {
                        window.location.reload();
                    },
                    1000
                );

                return;
            }

            // Backend failed
            if (
                result.status === "failed" ||
                result.success === false
            ) {
                throw new Error(
                    result.message ||
                        "Sinkronisasi ke Canva gagal."
                );
            }

            // Ambil URL Canva
            const canvaUrl =
                getCanvaUrl(result);

            console.log(
                "Response Sync:",
                result
            );

            console.log(
                "Canva URL:",
                canvaUrl
            );

            // Berhasil dan ada URL Canva
            if (canvaUrl) {
                showSyncSuccess(
                    result.message ||
                        "Harga berhasil dikirim ke Canva. Membuka Canva..."
                );

                setTimeout(
                    () => {
                        window.location.href =
                            canvaUrl;
                    },
                    700
                );

                return;
            }

            // Berhasil tetapi URL belum tersedia
            showSyncSuccess(
                result.message ||
                    "Sinkronisasi berhasil diproses."
            );

            setTimeout(
                () => {
                    window.location.reload();
                },
                1200
            );
        } catch (error) {
            console.error(
                "Sync Error:",
                error
            );

            showSyncError(
                error?.message ||
                    "Terjadi kesalahan saat melakukan sinkronisasi."
            );
        } finally {
            isSyncing = false;

            setButtonLoading(
                button,
                false
            );
        }
    }
);


}

/* ==========================================
GET CANVA URL
========================================== */

function getCanvaUrl(result) {
if (!result) {
return null;
}


// 1. edit_url
if (result.edit_url) {
    return result.edit_url;
}

// 2. canva_url
if (result.canva_url) {
    return result.canva_url;
}

// 3. data.edit_url
if (result.data?.edit_url) {
    return result.data.edit_url;
}

// 4. data.canva_url
if (result.data?.canva_url) {
    return result.data.canva_url;
}

// 5. design_id
const designId =
    result.design_id ||
    result.data?.design_id;

if (designId) {
    return buildCanvaEditUrl(
        designId
    );
}

return null;


}

/* ==========================================
BUILD CANVA EDIT URL
========================================== */

function buildCanvaEditUrl(
designId
) {
if (!designId) {
return null;
}


const cleanId =
    String(designId).trim();

if (!cleanId) {
    return null;
}

return `https://www.canva.com/design/${encodeURIComponent(
    cleanId
)}/edit`;


}

/* ==========================================
BUTTON LOADING
========================================== */

function setButtonLoading(
button,
loading
) {
if (!button) {
return;
}


if (loading) {
    // Simpan teks asli hanya sekali
    if (!button.dataset.originalText) {
        button.dataset.originalText =
            button.innerHTML;
    }

    button.disabled = true;

    button.classList.add(
        "is-loading"
    );

    button.innerHTML = `
        <span class="sync-spinner"></span>
        <span>MENYINKRONKAN KE CANVA...</span>
    `;
} else {
    button.disabled = false;

    button.classList.remove(
        "is-loading"
    );

    if (button.dataset.originalText) {
        button.innerHTML =
            button.dataset.originalText;
    }
}


}

/* ==========================================
SUCCESS ALERT
========================================== */

function showSyncSuccess(
message
) {
showSyncAlert(
message,
"success"
);
}

/* ==========================================
ERROR ALERT
========================================== */

function showSyncError(
message
) {
showSyncAlert(
message,
"error"
);
}

/* ==========================================
ALERT
========================================== */

function showSyncAlert(
message,
type = "success"
) {
// Hapus alert lama
const oldAlert =
document.getElementById(
"syncClientAlert"
);


if (oldAlert) {
    oldAlert.remove();
}

const alert =
    document.createElement(
        "div"
    );

alert.id =
    "syncClientAlert";

alert.className =
    type === "success"
        ? "sync-client-alert sync-client-success"
        : "sync-client-alert sync-client-error";

const icon =
    type === "success"
        ? "✓"
        : "×";

alert.innerHTML = `
    <div class="sync-alert-icon">
        ${icon}
    </div>

    <div class="sync-alert-content">
        <strong>
            ${
                type === "success"
                    ? "Berhasil"
                    : "Sinkronisasi Gagal"
            }
        </strong>

        <span>
            ${escapeHtml(message)}
        </span>
    </div>

    <button
        type="button"
        class="sync-alert-close"
        aria-label="Tutup"
    >
        ×
    </button>
`;

document.body.appendChild(
    alert
);

// Close button
const closeButton =
    alert.querySelector(
        ".sync-alert-close"
    );

if (closeButton) {
    closeButton.addEventListener(
        "click",
        () => {
            alert.classList.add(
                "closing"
            );

            setTimeout(
                () => {
                    alert.remove();
                },
                250
            );
        }
    );
}

// Animasi masuk
requestAnimationFrame(() => {
    alert.classList.add(
        "show"
    );
});

// Error tampil lebih lama
const duration =
    type === "error"
        ? 7000
        : 4000;

setTimeout(
    () => {
        if (
            !document.body.contains(
                alert
            )
        ) {
            return;
        }

        alert.classList.add(
            "closing"
        );

        setTimeout(
            () => {
                alert.remove();
            },
            250
        );
    },
    duration
);


}



function escapeHtml(value) {
const div =
document.createElement(
"div"
);


div.textContent =
    value === null ||
    value === undefined
        ? ""
        : String(value);

return div.innerHTML;


}
