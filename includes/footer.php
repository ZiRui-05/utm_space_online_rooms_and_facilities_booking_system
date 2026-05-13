<style>
    .footer {
        background: var(--text-dark);
        color: var(--white);
        padding: 30px;
        text-align: center;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 30px;
        flex-wrap: wrap;
    }

    .footer-section {
        flex: 1;
        min-width: 200px;
    }

    .footer-section h5 {
        font-size: 14px;
        margin-bottom: 8px;
    }

    .footer-section p {
        font-size: 13px;
        opacity: 0.8;
    }

    .footer-section a {
        color: var(--white);
        text-decoration: none;
        font-size: 13px;
        opacity: 0.8;
        transition: opacity 0.3s;
        margin: 0 12px;
    }

    .footer-section a:hover {
        opacity: 1;
    }

    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h5>UniReserve</h5>
            <p>© 2024 University Facilities Management. All rights reserved.</p>
        </div>
        <div class="footer-section">
            <a href="#">Terms of Service</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Accessibility</a>
            <a href="#">Contact Support</a>
        </div>
    </div>
</footer>