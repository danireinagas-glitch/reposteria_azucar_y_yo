document.addEventListener('click', async (event) => {
    const button = event.target.closest('.js-product-like');
    if (!button || button.disabled) {
        return;
    }

    const liked = !button.classList.contains('is-liked');
    button.disabled = true;

    try {
        const response = await fetch(button.dataset.likeUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ liked }),
        });

        if (!response.ok) {
            throw new Error('No se pudo guardar el me gusta.');
        }

        const result = await response.json();
        button.classList.toggle('is-liked', result.liked);
        button.setAttribute('aria-pressed', String(result.liked));
        button.querySelector('i').className = `bi ${result.liked ? 'bi-heart-fill' : 'bi-heart'}`;
        button.title = result.liked ? 'Quitar me gusta' : 'Me gusta';
        button.setAttribute(
            'aria-label',
            `${result.liked ? 'Quitar me gusta a' : 'Me gusta'} ${button.closest('.product-card').querySelector('.product-title').textContent.trim()}`
        );
    } catch (error) {
        window.alert(error.message || 'No se pudo guardar el me gusta. Inténtalo de nuevo.');
    } finally {
        button.disabled = false;
    }
});
