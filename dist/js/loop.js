// GSAP ScrollTrigger Looping Animation
gsap.registerPlugin(ScrollTrigger);

// Looping animation
gsap.to(".animated-text", {
    scrollTrigger: {
        trigger: ".content", // The element that will trigger the animation
        start: "top top", // When the top of the element reaches the top of the viewport
        end: "bottom top", // When the bottom of the element reaches the top of the viewport
        scrub: true, // Link animation to the scroll position
        loop: true // This makes the animation loop as the user scrolls
    },
    opacity: 0, // Make the text fade out as it scrolls
    x: 100, // Move the text horizontally to the right
    duration: 1, // Duration for each scroll trigger
    ease: "power1.inOut" // Ease the transition
});
