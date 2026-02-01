// DOM Elements
document.addEventListener('DOMContentLoaded', () => {
    
    // Sidebar Toggle for Mobile
    const createSidebarToggle = () => {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (!sidebar || !mainContent) return;

        const toggleBtn = document.createElement('button');
        toggleBtn.innerHTML = '☰';
        toggleBtn.className = 'sidebar-toggle';
        toggleBtn.style.cssText = `
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            display: none; 
        `;
        
        document.body.appendChild(toggleBtn);

        // Check screen size
        const checkScreen = () => {
            if (window.innerWidth <= 768) {
                toggleBtn.style.display = 'block';
                sidebar.style.transform = 'translateX(-100%)';
                sidebar.style.transition = 'transform 0.3s ease';
                mainContent.style.marginLeft = '0';
            } else {
                toggleBtn.style.display = 'none';
                sidebar.style.transform = 'none';
                mainContent.style.marginLeft = '250px';
            }
        };

        checkScreen();
        window.addEventListener('resize', checkScreen);

        toggleBtn.addEventListener('click', () => {
            if (sidebar.style.transform === 'translateX(-100%)') {
                sidebar.style.transform = 'translateX(0)';
            } else {
                sidebar.style.transform = 'translateX(-100%)';
            }
        });
    };

    createSidebarToggle();

    // Fade out messages
    const message = document.querySelector('div[style*="background-color: #dcfce7"]'); // Success msg
    if (message) {
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 1s';
            setTimeout(() => message.remove(), 1000);
        }, 3000);
    }

    // Dynamic Priority Color Update in Forms
    const prioritySelect = document.querySelector('select[name="priority"]');
    if (prioritySelect) {
        prioritySelect.addEventListener('change', (e) => {
            const val = e.target.value;
            if (val === 'High') e.target.style.borderColor = 'var(--danger)';
            else if (val === 'Medium') e.target.style.borderColor = 'var(--warning)';
            else e.target.style.borderColor = '#e2e8f0';
        });
    }

});
