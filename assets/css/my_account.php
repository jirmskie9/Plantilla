<style>
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }

        .activity-item {
            position: relative;
            padding-bottom: 20px;
        }

        .activity-item:last-child {
            padding-bottom: 0;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }

        .activity-icon {
            position: absolute;
            left: -30px;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border: 2px solid #0d6efd;
        }

        .activity-content {
            padding-left: 20px;
        }
    </style>