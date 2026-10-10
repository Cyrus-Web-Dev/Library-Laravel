<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import * as THREE from 'three';

const container = ref(null);
let renderer, scene, camera, animationId;
const books = [];

onMounted(() => {
    if (!container.value) return;

    const width = container.value.clientWidth;
    const height = container.value.clientHeight || 400;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
    camera.position.set(0, 1.5, 6);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x000000, 0);
    container.value.appendChild(renderer.domElement);

    // Soft ambient + directional light
    scene.add(new THREE.AmbientLight(0xffffff, 0.6));
    const dir = new THREE.DirectionalLight(0xffffff, 0.9);
    dir.position.set(5, 10, 7);
    scene.add(dir);

    // Create floating books
    const colors = [0x1f4d43, 0x3b7a6b, 0xc4a35a, 0x8b5e3c, 0x2c3e50, 0x6b4e71];
    for (let i = 0; i < 12; i++) {
        const geo = new THREE.BoxGeometry(0.6, 0.9, 0.12);
        const mat = new THREE.MeshStandardMaterial({
            color: colors[i % colors.length],
            roughness: 0.4,
            metalness: 0.1,
        });
        const book = new THREE.Mesh(geo, mat);
        book.position.set(
            (Math.random() - 0.5) * 8,
            (Math.random() - 0.5) * 3,
            (Math.random() - 0.5) * 3
        );
        book.rotation.set(
            Math.random() * 0.4,
            Math.random() * Math.PI,
            Math.random() * 0.3
        );
        book.userData = {
            speed: 0.2 + Math.random() * 0.3,
            amp: 0.3 + Math.random() * 0.4,
            phase: Math.random() * Math.PI * 2,
            rotSpeed: (Math.random() - 0.5) * 0.01,
        };
        scene.add(book);
        books.push(book);
    }

    // Subtle ground fog plane
    const planeGeo = new THREE.PlaneGeometry(20, 20);
    const planeMat = new THREE.MeshBasicMaterial({
        color: 0x1f4d43,
        transparent: true,
        opacity: 0.05,
    });
    const plane = new THREE.Mesh(planeGeo, planeMat);
    plane.rotation.x = -Math.PI / 2;
    plane.position.y = -2;
    scene.add(plane);

    const clock = new THREE.Clock();
    function animate() {
        animationId = requestAnimationFrame(animate);
        const t = clock.getElapsedTime();
        books.forEach((b) => {
            b.position.y += Math.sin(t * b.userData.speed + b.userData.phase) * 0.002;
            b.rotation.y += b.userData.rotSpeed;
        });
        renderer.render(scene, camera);
    }
    animate();

    const onResize = () => {
        if (!container.value || !renderer) return;
        const w = container.value.clientWidth;
        const h = container.value.clientHeight || 400;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    };
    window.addEventListener('resize', onResize);

    onUnmounted(() => {
        window.removeEventListener('resize', onResize);
        cancelAnimationFrame(animationId);
        if (renderer) {
            renderer.dispose();
            if (container.value && renderer.domElement) {
                container.value.removeChild(renderer.domElement);
            }
        }
        books.length = 0;
    });
});
</script>

<template>
    <div ref="container" class="absolute inset-0 -z-10 overflow-hidden opacity-80"></div>
</template>
