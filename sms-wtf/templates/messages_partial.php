<?php if (empty($messages)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <h4>No messages found</h4>
        <p class="text-muted">
            <?php if ($search): ?>
                No messages match your search criteria. Try different keywords.
            <?php elseif ($selectedPhone): ?>
                No messages received for this phone number yet.
            <?php else: ?>
                No SMS messages have been received yet. Make sure your webhook is configured correctly.
            <?php endif; ?>
        </p>
        <?php if (!$search): ?>
            <a href="admin/" class="btn btn-primary">
                <i class="bi bi-gear me-1"></i>Configure Webhook
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($messages as $message): ?>
        <div class="sms-message">
            <div class="sms-header">
                <div>
                    <?php if ($message['phone_display_name']): ?>
                        <span class="sms-phone-badge">
                            <i class="bi bi-telephone-fill me-1"></i>
                            <?php echo htmlspecialchars($message['phone_display_name']); ?>
                        </span>
                    <?php endif; ?>
                    <div class="sms-sender">
                        <i class="bi bi-person-fill me-2"></i>
                        <?php if ($message['sender_name']): ?>
                            <?php echo htmlspecialchars($message['sender_name']); ?>
                        <?php elseif ($message['sender_number']): ?>
                            <?php echo htmlspecialchars($message['sender_number']); ?>
                        <?php else: ?>
                            <span class="text-muted">Unknown Sender</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($message['sender_number'] && $message['sender_name']): ?>
                        <div class="text-muted small">
                            <i class="bi bi-telephone me-1"></i>
                            <?php echo htmlspecialchars($message['sender_number']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="sms-time">
                    <div class="fw-semibold">
                        <i class="bi bi-calendar-date me-1"></i>
                        <?php echo date('M j, Y', strtotime($message['received_at'])); ?>
                    </div>
                    <div class="text-muted">
                        <i class="bi bi-clock me-1"></i>
                        <?php echo date('g:i A', strtotime($message['received_at'])); ?>
                    </div>
                    <?php if (!$selectedPhone): ?>
                        <div class="text-primary mt-2 small">
                            <i class="bi bi-phone me-1"></i>
                            <?php echo htmlspecialchars($message['phone_number']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sms-content">
                <i class="bi bi-chat-quote-fill me-2 text-primary"></i>
                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
            </div>
            <div class="sms-actions">
                <button type="button" class="btn btn-outline-primary btn-sm" 
                        onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($message['message'])); ?>', this)"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Copy message to clipboard">
                    <i class="bi bi-clipboard me-1"></i>Copy Message
                </button>
                <?php if ($message['sender_number']): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="copyToClipboard('<?php echo htmlspecialchars($message['sender_number']); ?>', this)"
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Copy phone number">
                        <i class="bi bi-telephone me-1"></i>Copy Number
                    </button>
                <?php endif; ?>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots me-1"></i>More
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="#" onclick="shareMessage('<?php echo htmlspecialchars(addslashes($message['message'])); ?>')">
                                <i class="bi bi-share me-2"></i>Share Message
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="?search=<?php echo urlencode($message['sender_number'] ?? ''); ?>">
                                <i class="bi bi-search me-2"></i>Find More from This Sender
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                <i class="bi bi-info-circle me-2"></i>
                                Message ID: <?php echo $message['id']; ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
