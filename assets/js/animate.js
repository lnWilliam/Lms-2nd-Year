const links = document.querySelectorAll('.sidebar a');

        links.forEach(link => {
            link.addEventListener('click', function() {
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function isInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top <= window.innerHeight * 0.85 &&
                rect.bottom >= 0
            );
        }
        window.addEventListener("scroll", handleScroll);
        window.addEventListener("load", handleScroll);

        function handleScroll() {
            document.querySelectorAll(".card, .stat-box").forEach(el => {
                if (isInViewport(el)) {
                    el.classList.add("animate-up");
                }
            });
        }