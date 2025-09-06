export function playSuccessSound() {
    const audio = new Audio("/audio/success.mp3");
    audio.play().catch((e) => console.error("Error playing sound:", e));
}

export function playDeleteSound() {
    const audio = new Audio("/audio/delete.mp3");
    audio.play().catch((e) => console.error("Error playing sound:", e));
}

export function playDecreaseSound() {
    const audio = new Audio("/audio/decrease.mp3");
    audio.play().catch((e) => console.error("Error playing sound:", e));
}

export function playCashSound() {
    const audio = new Audio("/audio/cash.mp3");
    audio.play().catch((e) => console.error("Error playing sound:", e));
}
