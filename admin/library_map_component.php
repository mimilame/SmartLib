<?php 
/**
 * Library Map Component
 * Displays a consistent map with fixed library features and rack positioning
 * 
 * @param string $mode View mode ('view', 'edit', 'add', or 'list')
 * @param array $rack Optional rack data for edit/view modes
 * @param array $allRacks Optional array of all racks for list mode
 * @param int $height Optional map height (default: 300)
 * @param int $currentRackId Optional current rack ID for highlighting in list mode
 */
function renderLibraryMap($mode = 'add', $rack = null, $allRacks = [], $height = 300, $currentRackId = null, $libraryFeatures = []) {
    // Default positions if not provided
    $position_x = isset($rack['position_x']) ? $rack['position_x'] : 100;
    $position_y = isset($rack['position_y']) ? $rack['position_y'] : 100;
    
    // Determine rack color based on status
    $rackColor = 'primary';
    if ($mode === 'view') {
        $rackColor = 'info';
    } elseif (isset($rack['location_rack_status']) && $rack['location_rack_status'] !== 'Enable') {
        $rackColor = 'danger';
    }
    
    // Determine if rack is draggable
    $isDraggable = ($mode === 'add' || $mode === 'edit');
    $cursor = $isDraggable ? 'move' : 'default';
    $dragBorder = $isDraggable ? 'border: 2px dashed #007bff;' : '';
    $zIndex = $isDraggable ? 'z-index: 100;' : '';
    
    // Create unique ID for map container
    $mapId = 'library-map-' . uniqid();
?>
<div id="<?= $mapId ?>" class="rack-map-container position-relative" style="height: <?= $height ?>px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
    <!-- Map Grid -->
    <div class="library-map" style="width: 100%; height: 100%; overflow: hidden; background-image: linear-gradient(#e9ecef 1px, transparent 1px), linear-gradient(90deg, #e9ecef 1px, transparent 1px); background-size: 50px 50px;">
     
        <div id="map-content" style="transform-origin: center center; transition: transform 0.2s ease;">
            <?php if ($mode === 'list'): ?>
            <!-- List Mode: Show all racks -->
            <?php foreach ($allRacks as $listRack): ?>
                <?php
                    $listRackColor = 'success';
                    
                    // Handle rack status colors
                    if (isset($listRack['location_rack_status']) && $listRack['location_rack_status'] !== 'Enable') {
                        $listRackColor = 'danger';
                    }
                    
                    // Highlight the current rack if specified
                    if (isset($currentRackId) && isset($listRack['location_rack_id']) && $listRack['location_rack_id'] == $currentRackId) {
                        $listRackColor = 'info';
                    }
                    
                    $listPositionX = isset($listRack['position_x']) ? $listRack['position_x'] : 0;
                    $listPositionY = isset($listRack['position_y']) ? $listRack['position_y'] : 0;
                    $rackId = isset($listRack['location_rack_id']) ? $listRack['location_rack_id'] : null;
                ?>
                <a href="<?= $rackId ? "location_rack.php?action=view&code={$rackId}" : "#" ?>" class="text-decoration-none">
                    <div class="position-absolute d-flex justify-content-center align-items-center bg-<?= $listRackColor ?> text-white" 
                        style="width: 50px; height: 50px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <i class="fas fa-archive"></i>
                    </div>
                    
                    <!-- Rack Label -->
                    <?php if (isset($listRack['location_rack_name'])): ?>
                    <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label" 
                        style="top: <?= $listPositionY + 55 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.8rem; z-index: 90;">
                        <?= htmlspecialchars($listRack['location_rack_name']) ?>
                    </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            
            <?php elseif ($mode === 'add' || $mode === 'edit'): ?>
            <!-- Add/Edit Mode: Show draggable marker and other racks -->
            
            <!-- Show Other Racks (Secondary) -->
            <?php foreach ($allRacks as $listRack): ?>
                <?php
                    // Skip the current rack being edited
                    if ($mode === 'edit' && isset($rack['location_rack_id']) && isset($listRack['location_rack_id']) && $listRack['location_rack_id'] == $rack['location_rack_id']) {
                        continue;
                    }
                    
                    $listPositionX = isset($listRack['position_x']) ? $listRack['position_x'] : 0;
                    $listPositionY = isset($listRack['position_y']) ? $listRack['position_y'] : 0;
                    $listRackName = isset($listRack['location_rack_name']) ? $listRack['location_rack_name'] : '';
                ?>
                <div class="position-absolute d-flex justify-content-center align-items-center bg-secondary text-white opacity-75" 
                    style="width: 50px; height: 50px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <i class="fas fa-archive"></i>
                </div>
                
                <!-- Other rack labels -->
                <?php if ($listRackName): ?>
                <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label opacity-75" 
                    style="top: <?= $listPositionY + 55 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.8rem; z-index: 89;">
                    <?= htmlspecialchars($listRackName) ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <!-- Draggable Rack Element (Emphasized) -->
            <div id="rack-position-marker" 
                class="position-absolute d-flex justify-content-center align-items-center bg-<?= $rackColor ?> text-white fw-bold" 
                style="width: 50px; height: 50px; cursor: <?= $cursor ?>; border-radius: 4px; top: <?= $position_y ?>px; left: <?= $position_x ?>px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); <?= $dragBorder ?> <?= $zIndex ?>">
                <i class="fas fa-archive"></i>
            </div>
            
            <!-- Draggable rack label -->
            <?php if (isset($rack['location_rack_name'])): ?>
            <div id="rack-label" class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label" 
                style="top: <?= $position_y + 55 ?>px; left: <?= $position_x ?>px; transform: translateX(-25%); font-size: 0.8rem; z-index: 90;">
                <?= htmlspecialchars($rack['location_rack_name']) ?>
            </div>
            <?php endif; ?>
            
            <?php elseif ($mode === 'view'): ?>
            <!-- View Mode: Show single rack in primary while other existing with secondary -->
            
            <!-- Show all other racks in secondary color -->
            <?php foreach ($allRacks as $listRack): ?>
                <?php
                    // Skip the current rack being viewed
                    if (isset($rack['location_rack_id']) && isset($listRack['location_rack_id']) && $listRack['location_rack_id'] == $rack['location_rack_id']) {
                        continue;
                    }
                    
                    $listPositionX = isset($listRack['position_x']) ? $listRack['position_x'] : 0;
                    $listPositionY = isset($listRack['position_y']) ? $listRack['position_y'] : 0;
                    $otherRackId = isset($listRack['location_rack_id']) ? $listRack['location_rack_id'] : null;
                ?>
                <a href="<?= $otherRackId ? "location_rack.php?action=view&code={$otherRackId}" : "#" ?>" class="text-decoration-none">
                    <div class="position-absolute d-flex justify-content-center align-items-center bg-secondary text-white opacity-75" 
                        style="width: 50px; height: 50px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <i class="fas fa-archive"></i>
                    </div>
                    
                    <!-- Other rack labels -->
                    <?php if (isset($listRack['location_rack_name'])): ?>
                    <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label opacity-75" 
                        style="top: <?= $listPositionY + 55 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.8rem; z-index: 89;">
                        <?= htmlspecialchars($listRack['location_rack_name']) ?>
                    </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            
            <!-- Current rack being viewed (highlighted) -->
            <div id="static-rack" 
                class="position-absolute d-flex justify-content-center align-items-center bg-<?= $rackColor ?> text-white" 
                style="width: 50px; height: 50px; cursor: <?= $cursor ?>; border-radius: 4px; top: <?= $position_y ?>px; left: <?= $position_x ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 91;">
                <i class="fas fa-archive"></i>
            </div>
            
            <?php if (isset($rack['location_rack_name'])): ?>
            <!-- Rack Label (view mode) -->
            <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label" 
                style="top: <?= $position_y + 55 ?>px; left: <?= $position_x ?>px; transform: translateX(-25%); font-size: 0.8rem; z-index: 92;">
                <?= htmlspecialchars($rack['location_rack_name']) ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <!-- Dynamic Library Features - common across all views -->
            <?php foreach ($libraryFeatures as $feature): ?>
                <?php if ($feature['feature_status'] === 'Enable'): ?>
                <div class="position-absolute bg-<?= $feature['bg_color'] ?> text-<?= $feature['text_color'] ?> d-flex justify-content-center align-items-center" 
                    style="width: <?= $feature['width'] ?>px; height: <?= $feature['height'] ?>px; top: <?= $feature['position_y'] ?>px; left: <?= $feature['position_x'] ?>px; font-size: 0.8rem; border-radius: 4px; z-index: 80;">
                    <i class="<?= $feature['feature_icon'] ?> me-2"></i>
                    <?php if (!empty($feature['feature_name'])): ?>
                        <span class="feature-label"><?= htmlspecialchars($feature['feature_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <!-- Fixed Library Features - common across all views -->
                <!-- <div class="position-absolute bg-secondary text-white d-flex justify-content-center align-items-center" 
                    style="width: 150px; height: 40px; top: 5px; left: 5px; font-size: 0.8rem; border-radius: 4px; z-index: 80;">
                    <i class="fas fa-door-open me-2"></i>
                </div>
                
                <div class="position-absolute bg-warning text-dark d-flex justify-content-center align-items-center" 
                    style="width: 100px; height: 75px; bottom: 10px; right: 10px; font-size: 0.8rem; border-radius: 4px; z-index: 80;">
                    <i class="fas fa-book-reader me-2"></i>
                </div>
                
                <div class="position-absolute bg-info text-white d-flex justify-content-center align-items-center" 
                    style="width: 120px; height: 40px; top: 100px; right: 50px; font-size: 0.8rem; border-radius: 4px; z-index: 80;">
                    <i class="fas fa-desktop me-2"></i>
                </div>
                
                <div class="position-absolute bg-dark-subtle text-dark d-flex justify-content-center align-items-center" 
                    style="width: 70px; height: 40px; bottom: 80px; left: 40px; font-size: 0.8rem; border-radius: 4px; z-index: 80;">
                    <i class="fas fa-user-tie me-2"></i>
                </div> -->
        </div>
    </div>
    <div class="zoom-controls position-absolute end-0 bottom-0 m-3 d-flex flex-column gap-2">
        <button id="zoom-in" class="btn btn-primary btn-sm rounded-circle shadow-sm" 
                data-bs-toggle="tooltip" title="Zoom In" type="button">
            <i class="fas fa-plus"></i>
        </button>
        <button id="zoom-out" class="btn btn-primary btn-sm rounded-circle shadow-sm" 
                data-bs-toggle="tooltip" title="Zoom Out" type="button">
            <i class="fas fa-minus"></i>
        </button>
        <button id="reset-zoom" class="btn btn-secondary btn-sm rounded-circle shadow-sm" 
                data-bs-toggle="tooltip" title="Reset Zoom" type="button">
            <i class="fas fa-expand"></i>
        </button>
    </div>

    <?php if ($isDraggable): ?>
    <!-- Hidden inputs for position (only in editable modes) -->
    <input type="hidden" name="position_x" id="position_x" value="<?= $position_x ?>">
    <input type="hidden" name="position_y" id="position_y" value="<?= $position_y ?>">
    <?php endif; ?>
</div>

<?php if ($isDraggable): ?>
<div class="text-muted mt-2">
    <small><i class="fas fa-info-circle me-1"></i> Drag the rack marker to <?= $mode === 'add' ? 'position' : 'update its position' ?> on the library map.</small>
</div>

<!-- Draggable Functionality Script -->
<script>
    // Modern zoom functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mapContainer = document.querySelector('.library-map');
        const mapContent = document.getElementById('map-content');
        const zoomInBtn = document.getElementById('zoom-in');
        const zoomOutBtn = document.getElementById('zoom-out');
        const resetZoomBtn = document.getElementById('reset-zoom');
        
        // Only initialize if elements exist
        if (!mapContainer || !mapContent) return;
        
        let zoomLevel = 1;
        const zoomStep = 0.2;
        const minZoom = 0.5;
        const maxZoom = 3;
        let isDragging = false;
        let startX, startY, scrollLeft, scrollTop;
        
        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            [zoomInBtn, zoomOutBtn, resetZoomBtn].forEach(btn => {
                new bootstrap.Tooltip(btn);
            });
        }
        
        // Prevent button clicks from propagating to parent
        [zoomInBtn, zoomOutBtn, resetZoomBtn].forEach(btn => {
            btn?.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
            });
        });
        
        // Update zoom transform
        function updateZoom() {
            mapContent.style.transform = `scale(${zoomLevel})`;
            mapContent.style.transformOrigin = 'center center';
        }
        
        // Smooth zoom function
        function smoothZoom(targetZoom) {
            const duration = 200;
            const startZoom = zoomLevel;
            const change = targetZoom - startZoom;
            const startTime = performance.now();
            
            function animateZoom(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3); // easeOutCubic
                
                zoomLevel = startZoom + (change * easeProgress);
                updateZoom();
                
                if (progress < 1) {
                    requestAnimationFrame(animateZoom);
                }
            }
            
            requestAnimationFrame(animateZoom);
        }
        
        // Button event handlers
        zoomInBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (zoomLevel < maxZoom) {
                smoothZoom(Math.min(zoomLevel + zoomStep, maxZoom));
            }
        });
        
        zoomOutBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (zoomLevel > minZoom) {
                smoothZoom(Math.max(zoomLevel - zoomStep, minZoom));
            }
        });
        
        resetZoomBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            smoothZoom(1);
        });
        
        // Mouse wheel zoom with Ctrl key
        mapContainer.addEventListener('wheel', (e) => {
            if (e.ctrlKey) {
                e.preventDefault();
                const delta = -Math.sign(e.deltaY) * zoomStep;
                const newZoom = Math.min(Math.max(zoomLevel + delta, minZoom), maxZoom);
                
                if (newZoom !== zoomLevel) {
                    smoothZoom(newZoom);
                }
            }
        }, { passive: false });
        
        // Mouse panning
        mapContainer.addEventListener('mousedown', (e) => {
            // Only start panning if not clicking on a draggable rack
            if (!e.target.closest('.draggable-rack')) {
                isDragging = true;
                startX = e.pageX - mapContainer.offsetLeft;
                startY = e.pageY - mapContainer.offsetTop;
                scrollLeft = mapContainer.scrollLeft;
                scrollTop = mapContainer.scrollTop;
                mapContainer.style.cursor = 'grabbing';
            }
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            
            const x = e.pageX - mapContainer.offsetLeft;
            const y = e.pageY - mapContainer.offsetTop;
            const walkX = (x - startX) * 2;
            const walkY = (y - startY) * 2;
            
            mapContainer.scrollLeft = scrollLeft - walkX;
            mapContainer.scrollTop = scrollTop - walkY;
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
            mapContainer.style.cursor = 'grab';
        });
        
        // Touch events for mobile
        let initialDistance = null;
        
        mapContainer.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                e.preventDefault();
                initialDistance = getDistance(e.touches[0], e.touches[1]);
            } else if (e.touches.length === 1) {
                if (!e.target.closest('.draggable-rack')) {
                    isDragging = true;
                    startX = e.touches[0].pageX - mapContainer.offsetLeft;
                    startY = e.touches[0].pageY - mapContainer.offsetTop;
                    scrollLeft = mapContainer.scrollLeft;
                    scrollTop = mapContainer.scrollTop;
                }
            }
        }, { passive: false });
        
        mapContainer.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2 && initialDistance !== null) {
                e.preventDefault();
                const currentDistance = getDistance(e.touches[0], e.touches[1]);
                const scale = currentDistance / initialDistance;
                const newZoom = Math.min(Math.max(zoomLevel * scale, minZoom), maxZoom);
                
                if (Math.abs(newZoom - zoomLevel) > 0.01) {
                    zoomLevel = newZoom;
                    updateZoom();
                }
            } else if (isDragging && e.touches.length === 1) {
                e.preventDefault();
                const x = e.touches[0].pageX - mapContainer.offsetLeft;
                const y = e.touches[0].pageY - mapContainer.offsetTop;
                const walkX = (x - startX) * 2;
                const walkY = (y - startY) * 2;
                mapContainer.scrollLeft = scrollLeft - walkX;
                mapContainer.scrollTop = scrollTop - walkY;
            }
        }, { passive: false });
        
        mapContainer.addEventListener('touchend', () => {
            initialDistance = null;
            isDragging = false;
        });
        
        function getDistance(touch1, touch2) {
            const dx = touch1.clientX - touch2.clientX;
            const dy = touch1.clientY - touch2.clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }
        
        // Initialize
        updateZoom();
        mapContainer.style.cursor = 'grab';
    });

    document.addEventListener('DOMContentLoaded', function() {
        const rackMarker = document.getElementById('rack-position-marker');
        const rackLabel = document.getElementById('rack-label');
        const positionX = document.getElementById('position_x');
        const positionY = document.getElementById('position_y');
        const mapContainer = document.getElementById('<?= $mapId ?>');
        
        if (!rackMarker || !positionX || !positionY || !mapContainer) return;
        
        let isDragging = false;
        let offsetX, offsetY;
        
        // Get container boundaries
        const containerRect = mapContainer.querySelector('.library-map').getBoundingClientRect();
        const containerLeft = containerRect.left;
        const containerTop = containerRect.top;
        const containerWidth = containerRect.width;
        const containerHeight = containerRect.height;
        
        // Initialize drag events
        rackMarker.addEventListener('mousedown', startDrag);
        
        function startDrag(e) {
            e.preventDefault();
            
            // Calculate the offset of the mouse position relative to the rack marker
            const rect = rackMarker.getBoundingClientRect();
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            
            isDragging = true;
            
            // Add event listeners for dragging and dropping
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', stopDrag);
        }
        
        function drag(e) {
            if (!isDragging) return;
            
            e.preventDefault();
            
            // Calculate new position
            let newLeft = e.clientX - containerRect.left - offsetX;
            let newTop = e.clientY - containerRect.top - offsetY;
            
            // Constrain to container boundaries
            newLeft = Math.min(Math.max(newLeft, 0), containerWidth - rackMarker.offsetWidth);
            newTop = Math.min(Math.max(newTop, 0), containerHeight - rackMarker.offsetHeight);
            
            // Snap to grid (50px)
            newLeft = Math.round(newLeft / 50) * 50;
            newTop = Math.round(newTop / 50) * 50;
            
            // Update marker position
            rackMarker.style.left = newLeft + 'px';
            rackMarker.style.top = newTop + 'px';
            
            // Update label position if it exists
            if (rackLabel) {
                rackLabel.style.left = newLeft + 'px';
                rackLabel.style.top = (newTop + 55) + 'px';
            }
            
            // Update hidden inputs
            positionX.value = newLeft;
            positionY.value = newTop;
        }
        
        function stopDrag() {
            isDragging = false;
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('mouseup', stopDrag);
        }
        
        // Touch support for mobile devices
        rackMarker.addEventListener('touchstart', function(e) {
            const touch = e.touches[0];
            const rect = rackMarker.getBoundingClientRect();
            offsetX = touch.clientX - rect.left;
            offsetY = touch.clientY - rect.top;
            
            document.addEventListener('touchmove', touchDrag);
            document.addEventListener('touchend', touchEnd);
        });
        
        function touchDrag(e) {
            e.preventDefault();
            const touch = e.touches[0];
            
            // Calculate new position
            let newLeft = touch.clientX - containerRect.left - offsetX;
            let newTop = touch.clientY - containerRect.top - offsetY;
            
            // Constrain to container boundaries
            newLeft = Math.min(Math.max(newLeft, 0), containerWidth - rackMarker.offsetWidth);
            newTop = Math.min(Math.max(newTop, 0), containerHeight - rackMarker.offsetHeight);
            
            // Snap to grid (50px)
            newLeft = Math.round(newLeft / 50) * 50;
            newTop = Math.round(newTop / 50) * 50;
            
            // Update marker position
            rackMarker.style.left = newLeft + 'px';
            rackMarker.style.top = newTop + 'px';
            
            // Update label position if it exists
            if (rackLabel) {
                rackLabel.style.left = newLeft + 'px';
                rackLabel.style.top = (newTop + 55) + 'px';
            }
            
            // Update hidden inputs
            positionX.value = newLeft;
            positionY.value = newTop;
        }
        
        function touchEnd() {
            document.removeEventListener('touchmove', touchDrag);
            document.removeEventListener('touchend', touchEnd);
        }
    });
</script>
<?php endif; ?>

<?php
}

/**
 * Example usage:
 * 
 * // List mode - show all racks with optional highlighting
 * renderLibraryMap('list', null, $allRacks, 400, $currentRackId);
 * 
 * // Add mode - draggable rack with other racks shown
 * renderLibraryMap('add', ['location_rack_name' => 'New Rack'], $allRacks);
 * 
 * // Edit mode - draggable rack with existing position and other racks shown
 * renderLibraryMap('edit', $rackData, $allRacks);
 * 
 * // View mode - static rack with other racks shown
 * renderLibraryMap('view', $rackData, $allRacks);
 */
?>