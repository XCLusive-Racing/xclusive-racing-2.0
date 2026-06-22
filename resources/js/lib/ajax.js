const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export async function post(url, data = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    });
    return { res, data: await res.json().catch(() => null) };
}

export async function del(url) {
    const res = await fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrf(),
            'Accept': 'application/json',
        },
    });
    return { res, data: await res.json().catch(() => null) };
}

export async function patch(url, data = {}) {
    const res = await fetch(url, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    });
    return { res, data: await res.json().catch(() => null) };
}

export async function get(url) {
    const res = await fetch(url, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    return { res, data: await res.json().catch(() => null) };
}
