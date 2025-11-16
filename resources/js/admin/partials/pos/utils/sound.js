// Pre-load audio files to prevent delay on rapid clicks and for notifications.
const sounds = {
    success: new Audio("/audio/success.mp3"),
    successpos: new Audio("/audio/successpos.mp3"),
    delete: new Audio("/audio/delete.mp3"),
    decrease: new Audio("/audio/decrease.mp3"),
    info: new Audio("/audio/info.mp3"),
    warning: new Audio("/audio/warning.mp3"),
    error: new Audio("/audio/error.mp3"),
};

function playSound(audioElement) {
    if (
        audioElement &&
        window.userSettings &&
        window.userSettings.enable_sound_notifications
    ) {
        audioElement.currentTime = 0; // Rewind to the start
        audioElement.play().catch((e) => {
            console.error("Error playing sound:", e);
        });
    }
}

export function playSuccessSound() {
    playSound(sounds.success);
}

export function playSuccessPosSound() {
    playSound(sounds.successpos);
}

export function playDeleteSound() {
    playSound(sounds.delete);
}

export function playDecreaseSound() {
    playSound(sounds.decrease);
}

export function playNotificationSound(type) {
    playSound(sounds[type]);
}
