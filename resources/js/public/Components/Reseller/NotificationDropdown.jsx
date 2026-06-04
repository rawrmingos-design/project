import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';


export default function NotificationDropdown({ userId }) {
    const { props } = usePage();
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    // Fetch initial notifications
    const fetchNotifications = async () => {
        try {
            const res = await axios.get('/id/reseller/notifications');
            setNotifications(res.data.data);
            
            const countRes = await axios.get('/id/reseller/notifications/unread-count');
            setUnreadCount(countRes.data.count);
        } catch (error) {
            console.error('Failed to fetch notifications', error);
        }
    };

    useEffect(() => {
        if (!userId) return;

        fetchNotifications();

        // Click outside to close
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);

        // Wait for window.Echo to be ready
        let retryCount = 0;
        const maxRetries = 50;
        const trySubscribe = () => {
            if (window.Echo) {
                // Log connection state changes
                const pusher = window.Echo.connector.pusher;
                pusher.connection.bind('state_change', (states) => {
                    console.log('[Echo] Connection state:', states.previous, '->', states.current);
                });
                pusher.connection.bind('error', (err) => {
                    console.error('[Echo] Connection error:', err);
                });

                console.log('[Notifications] Subscribing to private channel for user', userId);
                const channel = window.Echo.private(`App.Models.User.${userId}`);

                // Log subscription result
                channel.subscribed(() => {
                    console.log('[Notifications] ✅ Channel subscription SUCCEEDED!');
                });
                channel.error((error) => {
                    console.error('[Notifications] ❌ Channel subscription FAILED:', error);
                });

                // Listen for ALL events on channel to see what Reverb actually sends
                channel.listenToAll((eventName, data) => {
                    console.log('[Notifications] Raw event received:', eventName, data);
                });

                // Listen specifically for notification events
                channel.notification((notification) => {
                    console.log('[Notifications] ✅ Notification received:', notification);
                    setNotifications(prev => [notification, ...prev]);
                    setUnreadCount(prev => prev + 1);
                });

            } else if (retryCount < maxRetries) {
                retryCount++;
                setTimeout(trySubscribe, 200);
            } else {
                console.warn('[Notifications] window.Echo not available after 10s.');
            }
        };
        trySubscribe();

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            if (window.Echo) {
                window.Echo.leave(`App.Models.User.${userId}`);
            }
        };
    }, [userId]);

    const markAsRead = async (id) => {
        try {
            await axios.post(`/id/reseller/notifications/${id}/read`);
            setNotifications(prev => prev.map(n => 
                n.id === id ? { ...n, read_at: new Date().toISOString() } : n
            ));
            setUnreadCount(prev => Math.max(0, prev - 1));
        } catch (error) {
            console.error('Failed to mark as read', error);
        }
    };

    const markAllAsRead = async () => {
        try {
            await axios.post('/id/reseller/notifications/read-all');
            setNotifications(prev => prev.map(n => ({ ...n, read_at: new Date().toISOString() })));
            setUnreadCount(0);
        } catch (error) {
            console.error('Failed to mark all as read', error);
        }
    };

    const handleNotificationClick = (notification) => {
        if (!notification.read_at) {
            markAsRead(notification.id);
        }
        
        if (notification.data?.action_url) {
            window.location.href = notification.data.action_url;
        }
    };

    const formatTime = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' });
    };

    return (
        <div style={{ position: 'relative' }} ref={dropdownRef}>
            <button 
                className={`rh-icon-btn--stitch ${unreadCount > 0 ? 'rh-icon-btn--has-indicator' : ''}`} 
                title="Notifications"
                onClick={() => setIsOpen(!isOpen)}
            >
                <span className="material-symbols-outlined">notifications</span>
                {unreadCount > 0 && <span className="rh-indicator">{unreadCount}</span>}
            </button>

            {isOpen && (
                <div style={{
                    position: 'absolute',
                    top: '100%',
                    right: 0,
                    marginTop: '8px',
                    width: '320px',
                    maxHeight: '400px',
                    background: 'var(--surface-container)',
                    border: '1px solid var(--outline-variant)',
                    borderRadius: '12px',
                    boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.25)',
                    zIndex: 100,
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden'
                }}>
                    <div style={{
                        padding: '12px 16px',
                        borderBottom: '1px solid var(--outline-variant)',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        background: 'var(--surface-container-high)'
                    }}>
                        <h3 style={{ margin: 0, fontSize: '14px', fontWeight: 600 }}>Notifications</h3>
                        {unreadCount > 0 && (
                            <button 
                                onClick={markAllAsRead}
                                style={{
                                    background: 'none',
                                    border: 'none',
                                    color: 'var(--primary)',
                                    fontSize: '12px',
                                    cursor: 'pointer',
                                    padding: 0
                                }}
                            >
                                Mark all read
                            </button>
                        )}
                    </div>

                    <div style={{
                        overflowY: 'auto',
                        flex: 1,
                        padding: 0,
                        margin: 0
                    }}>
                        {notifications.length === 0 ? (
                            <div style={{ padding: '24px', textAlign: 'center', color: 'var(--on-surface-variant)' }}>
                                <span className="material-symbols-outlined" style={{ fontSize: '32px', opacity: 0.5 }}>notifications_off</span>
                                <p style={{ margin: '8px 0 0', fontSize: '14px' }}>No notifications yet</p>
                            </div>
                        ) : (
                            notifications.map(notification => (
                                <div 
                                    key={notification.id}
                                    onClick={() => handleNotificationClick(notification)}
                                    style={{
                                        padding: '12px 16px',
                                        borderBottom: '1px solid var(--outline-variant)',
                                        cursor: 'pointer',
                                        background: notification.read_at ? 'transparent' : 'rgba(13, 138, 188, 0.08)',
                                        display: 'flex',
                                        gap: '12px',
                                        alignItems: 'flex-start',
                                        transition: 'background 0.2s'
                                    }}
                                    onMouseOver={(e) => {
                                        if (notification.read_at) e.currentTarget.style.background = 'var(--surface-container-high)';
                                    }}
                                    onMouseOut={(e) => {
                                        if (notification.read_at) e.currentTarget.style.background = 'transparent';
                                    }}
                                >
                                    <div style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        width: '32px',
                                        height: '32px',
                                        borderRadius: '8px',
                                        background: notification.data?.color === 'success' ? 'rgba(34, 197, 94, 0.2)' : 
                                                    notification.data?.color === 'warning' ? 'rgba(245, 158, 11, 0.2)' : 
                                                    notification.data?.color === 'danger' ? 'rgba(239, 68, 68, 0.2)' : 
                                                    'rgba(13, 138, 188, 0.2)',
                                        color: notification.data?.color === 'success' ? '#4ade80' : 
                                               notification.data?.color === 'warning' ? '#fbbf24' : 
                                               notification.data?.color === 'danger' ? '#f87171' : 
                                               'var(--primary)',
                                        flexShrink: 0
                                    }}>
                                        <span className="material-symbols-outlined" style={{ fontSize: '18px' }}>
                                            {notification.data?.icon || 'notifications'}
                                        </span>
                                    </div>
                                    <div style={{ flex: 1, minWidth: 0 }}>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '2px' }}>
                                            <strong style={{ fontSize: '13px', color: notification.read_at ? 'var(--on-surface-variant)' : 'var(--on-surface)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                {notification.data?.title || 'Notification'}
                                            </strong>
                                            <span style={{ fontSize: '11px', color: 'var(--on-surface-variant)', flexShrink: 0, marginLeft: '8px' }}>
                                                {formatTime(notification.created_at)}
                                            </span>
                                        </div>
                                        <p style={{ margin: 0, fontSize: '12px', color: 'var(--on-surface-variant)', lineHeight: 1.4 }}>
                                            {notification.data?.message}
                                        </p>
                                    </div>
                                    {!notification.read_at && (
                                        <div style={{ width: '6px', height: '6px', borderRadius: '50%', background: 'var(--primary)', flexShrink: 0, marginTop: '13px' }} />
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
