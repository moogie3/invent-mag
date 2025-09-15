export function playSuccessSound() {
    if (window.userSettings && window.userSettings.enable_sound_notifications) {
        const audio = new Audio("/audio/success.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }
}

export function playDeleteSound() {
    if (window.userSettings && window.userSettings.enable_sound_notifications) {
        const audio = new Audio("/audio/delete.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }
}

export function playDecreaseSound() {
    if (window.userSettings && window.userSettings.enable_sound_notifications) {
        const audio = new Audio("/audio/decrease.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }
}

export function playCashSound() {
    if (window.userSettings && window.userSettings.enable_sound_notifications) {
        const audio = new Audio("/audio/cash.mp3");
        audio.play().catch((e) => console.error("Error playing sound:", e));
    }
}
