<?php if (empty($messages)): ?>
    <div class="text-center py-5">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
        <h4>No messages found</h4>
        <p class="text-muted">
            <?php if ($search): ?>
                No messages match your search criteria.
            <?php elseif ($selectedPhone): ?>
                No messages received for this phone number yet.
            <?php else: ?>
                No SMS messages have been received yet.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <?php foreach ($messages as $message): ?>
        <div class="sms-message">
            <div class="sms-header">
                <div>
                    <?php if ($message['phone_display_name']): ?>
                        <div class="sms-phone"><?php echo htmlspecialchars($message['phone_display_name']); ?></div>
                    <?php endif; ?>
                    <div class="sms-sender">
                        <?php if ($message['sender_name']): ?>
                            <?php echo htmlspecialchars($message['sender_name']); ?>
                        <?php elseif ($message['sender_number']): ?>
                            <?php echo htmlspecialchars($message['sender_number']); ?>
                        <?php else: ?>
                            Unknown Sender
                        <?php endif; ?>
                    </div>
                    <?php if ($message['sender_number'] && $message['sender_name']): ?>
                        <div class="sms-number"><?php echo htmlspecialchars($message['sender_number']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="sms-time">
                    <div><?php echo date('M j, Y', strtotime($message['received_at'])); ?></div>
                    <div><?php echo date('g:i A', strtotime($message['received_at'])); ?></div>
                    <?php if (!$selectedPhone): ?>
                        <div class="text-primary mt-1" style="font-size: 0.75rem;">
                            <?php echo htmlspecialchars($message['phone_number']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sms-content">
                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
            </div>
            <div class="sms-actions mt-2">
                <button type="button" class="btn btn-sm btn-outline" 
                        onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($message['message'])); ?>')">
                    Copy Message
                </button>
                <?php if ($message['sender_number']): ?>
                    <button type="button" class="btn btn-sm btn-outline" 
                            onclick="copyToClipboard('<?php echo htmlspecialchars($message['sender_number']); ?>')">
                        Copy Number
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
