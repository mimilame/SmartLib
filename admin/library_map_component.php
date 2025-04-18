<?php 
/**
 * Library Map Component
 * Displays a consistent map with fixed library features and rack positioning
 * 
 * @param string $mode View mode ('view', 'edit', 'add', or 'list')
 * @param array $rack Optional rack data for edit/view modes
 * @param array $allRacks Optional array of all racks for list mode
 * @param string $size Optional map size ('very_small', 'small', 'medium', 'large')
 * @param array $libraryFeatures Optional array of library feature objects
 */
function renderLibraryMap($mode = 'add', $rack = null, $allRacks = [], $size = 'medium', $libraryFeatures = []) {
    // Map size configurations in pixels (based on standard ratios)
    $mapSizes = [
        'very_small' => [
            'width' => 305,
            'height' => 275,
            'units' => ['3.05m × 2.75m', '10\' × 9\'']
        ],
        'small' => [
            'width' => 366,
            'height' => 335,
            'units' => ['3.66m × 3.35m', '12\' × 11\'']
        ],
        'medium' => [
            'width' => 671,
            'height' => 610,
            'units' => ['6.71m × 6.10m', '22\' × 20\'']
        ],
        'large' => [
            'width' => 910,
            'height' => 830,
            'units' => ['9.10m × 8.30m', '30\' × 27\'']
        ]
    ];
   
    
    // Default to medium if size is not recognized
    if (is_numeric($size)) {
        // Handle numeric input as height (backward compatibility)
        $mapHeight = intval($size);
        $mapWidth = intval($size * 1.2); // Approximate aspect ratio
        $sizeKey = 'custom';
        $sizeUnits = ['Custom', 'Custom'];
    } else {
        $sizeKey = isset($mapSizes[$size]) ? $size : 'medium';
        $mapWidth = $mapSizes[$sizeKey]['width'];
        $mapHeight = $mapSizes[$sizeKey]['height'];
        $sizeUnits = $mapSizes[$sizeKey]['units'];
    }
    
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
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <h6 class="m-0 d-flex align-items-center">
                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                Library Floor Plan
                <span class="badge bg-secondary ms-2">
                    <?= $sizeUnits[0] ?> / <?= $sizeUnits[1] ?>
                </span>
            </h6>
        </div>
        <?php if ($isDraggable): ?>
        <div>
            <small class="text-muted fst-italic">
                <i class="fas fa-hand-pointer me-1"></i>
                Drag to position | 
                <i class="fas fa-arrows-alt me-1"></i>
                Pan & zoom available
            </small>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div id="<?= $mapId ?>" class="rack-map-container position-relative overflow-hidden" style="height: <?= $mapHeight ?>px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0;">
            <!-- Map Container with Fixed Dimensions -->
            <div class="library-map" style="width: 100%; height: 100%; overflow: hidden; position: relative;">
                <div id="map-content-<?= $mapId ?>" class="map-content position-absolute" style="width: <?= $mapWidth ?>px; height: <?= $mapHeight ?>px; transform-origin: 0 0; background-image: linear-gradient(#e9ecef 1px, transparent 1px), linear-gradient(90deg, #e9ecef 1px, transparent 1px); background-size: 50px 50px; transition: transform 0.2s ease;">
                    <?php if ($mode === 'list'): ?>
                    <!-- List Mode: Show all racks -->
                    <?php foreach ($allRacks as $listRack): ?>
                        <?php
                            $listRackColor = 'primary';
                            
                            // Handle rack status colors
                            if (isset($listRack['location_rack_status']) && $listRack['location_rack_status'] !== 'Enable') {
                                $listRackColor = 'danger';
                            }
                            
                            // Highlight the current rack if specified
                            if (isset($libraryFeatures) && isset($listRack['location_rack_id']) && $listRack['location_rack_id'] == $libraryFeatures) {
                                $listRackColor = 'info';
                            }
                            
                            $listPositionX = isset($listRack['position_x']) ? $listRack['position_x'] : 0;
                            $listPositionY = isset($listRack['position_y']) ? $listRack['position_y'] : 0;
                            $rackId = isset($listRack['location_rack_id']) ? $listRack['location_rack_id'] : null;
                        ?>
                        <a href="<?= $rackId ? "location_rack.php?action=view&code={$rackId}" : "#" ?>" class="text-decoration-none">
                            <div class="position-absolute d-flex justify-content-center align-items-center bg-<?= $listRackColor ?> text-white" 
                                style="width: 40px; height: 40px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <i class="fas fa-archive"></i>
                            </div>
                            
                            <!-- Rack Label -->
                            <?php if (isset($listRack['location_rack_name'])): ?>
                            <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label" 
                                style="top: <?= $listPositionY + 45 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 90; white-space: nowrap;">
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
                            style="width: 40px; height: 40px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fas fa-archive"></i>
                        </div>
                        
                        <!-- Other rack labels -->
                        <?php if ($listRackName): ?>
                        <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label opacity-75" 
                            style="top: <?= $listPositionY + 45 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 89; white-space: nowrap;">
                            <?= htmlspecialchars($listRackName) ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <!-- Draggable Rack Element (Emphasized) -->
                    <div id="rack-position-marker-<?= $mapId ?>" 
                        class="position-absolute d-flex justify-content-center align-items-center bg-<?= $rackColor ?> text-white fw-bold draggable-rack" 
                        style="width: 40px; height: 40px; cursor: <?= $cursor ?>; border-radius: 4px; top: <?= $position_y ?>px; left: <?= $position_x ?>px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); <?= $dragBorder ?> <?= $zIndex ?>">
                        <i class="fas fa-archive"></i>
                    </div>
                    
                    <!-- Draggable rack label -->
                    <?php if (isset($rack['location_rack_name'])): ?>
                    <div id="rack-label-<?= $mapId ?>" class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label" 
                        style="top: <?= $position_y + 45 ?>px; left: <?= $position_x ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 90; white-space: nowrap;">
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
                                style="width: 40px; height: 40px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <i class="fas fa-archive"></i>
                            </div>
                            
                            <!-- Other rack labels -->
                            <?php if (isset($listRack['location_rack_name'])): ?>
                            <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label opacity-75" 
                                style="top: <?= $listPositionY + 45 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 89; white-space: nowrap;">
                                <?= htmlspecialchars($listRack['location_rack_name']) ?>
                            </div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Current rack being viewed (highlighted) -->
                    <div id="static-rack-<?= $mapId ?>" 
                        class="position-absolute d-flex justify-content-center align-items-center bg-<?= $rackColor ?> text-white" 
                        style="width: 40px; height: 40px; cursor: <?= $cursor ?>; border-radius: 4px; top: <?= $position_y ?>px; left: <?= $position_x ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 91;">
                        <i class="fas fa-archive"></i>
                    </div>
                    
                    <?php if (isset($rack['location_rack_name'])): ?>
                    <!-- Rack Label (view mode) -->
                    <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label" 
                        style="top: <?= $position_y + 45 ?>px; left: <?= $position_x ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 92; white-space: nowrap;">
                        <?= htmlspecialchars($rack['location_rack_name']) ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Library Features - common across all views -->
                    <?php foreach ($libraryFeatures as $feature): ?>
                        <?php if (isset($feature['feature_status']) && $feature['feature_status'] === 'Enable'): ?>
                        <div class="position-absolute <?= $feature['bg_color'] ?> <?= $feature['text_color'] ?> d-flex justify-content-center align-items-center" 
                            style="width: <?= $feature['width'] ?>px; height: <?= $feature['height'] ?>px; top: <?= $feature['position_y'] ?>px; left: <?= $feature['position_x'] ?>px; font-size: 0.8rem; border-radius: 4px; z-index: 80;">
                            <i class="<?= $feature['feature_icon'] ?> me-2"></i>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Zoom and Pan Controls -->
            <div class="zoom-controls position-absolute end-0 bottom-0 m-2 d-flex flex-column gap-2">
                <button id="zoom-in-<?= $mapId ?>" class="btn btn-sm btn-light rounded-circle shadow-sm" 
                        data-bs-toggle="tooltip" title="Zoom In" type="button">
                    <i class="fas fa-plus"></i>
                </button>
                <button id="zoom-out-<?= $mapId ?>" class="btn btn-sm btn-light rounded-circle shadow-sm" 
                        data-bs-toggle="tooltip" title="Zoom Out" type="button">
                    <i class="fas fa-minus"></i>
                </button>
                <button id="reset-zoom-<?= $mapId ?>" class="btn btn-sm btn-secondary rounded-circle shadow-sm" 
                        data-bs-toggle="tooltip" title="Reset View" type="button">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            
            <?php if ($isDraggable): ?>
            <!-- Coordinates Display -->
            <div class="position-absolute start-0 bottom-0 m-2 p-1 bg-white rounded shadow-sm">
                <small class="text-muted">
                    Position: <span id="position-display-<?= $mapId ?>">X: <?= $position_x ?>, Y: <?= $position_y ?></span>
                </small>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($isDraggable): ?>
    <!-- Hidden inputs for position (only in editable modes) -->
    <input type="hidden" name="position_x" id="position_x-<?= $mapId ?>" value="<?= $position_x ?>">
    <input type="hidden" name="position_y" id="position_y-<?= $mapId ?>" value="<?= $position_y ?>">
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to initialize all maps on the page
    initializeAllMaps();
    
    // Function to initialize all feature previews on the page
    initializeFeaturePreviews();
});

function initializeAllMaps() {
    // Find all map containers on the page
    const mapContainers = document.querySelectorAll('[id^="library-map-"]');
    
    mapContainers.forEach(container => {
        const mapId = container.id;
        initializeMap(mapId);
    });
}

function initializeMap(mapId) {
    const mapContainer = document.querySelector('#' + mapId + ' .library-map');
    const mapContent = document.getElementById('map-content-' + mapId);
    const zoomInBtn = document.getElementById('zoom-in-' + mapId);
    const zoomOutBtn = document.getElementById('zoom-out-' + mapId);
    const resetZoomBtn = document.getElementById('reset-zoom-' + mapId);
    
    // Only initialize if elements exist
    if (!mapContainer || !mapContent) return;
    
    // Pan and Zoom Variables
    let zoomLevel = 1;
    const zoomStep = 0.1;
    const minZoom = 0.5;
    const maxZoom = 3;
    let isPanning = false;
    let startX, startY, lastX, lastY;
    let translateX = 0;
    let translateY = 0;
    

    
    // Update transform
    function updateTransform() {
        mapContent.style.transform = `translate(${translateX}px, ${translateY}px) scale(${zoomLevel})`;
    }
    
    // Smooth zoom function
    function smoothZoom(targetZoom, centerX = null, centerY = null) {
        const duration = 200;
        const startZoom = zoomLevel;
        const change = targetZoom - startZoom;
        const startTime = performance.now();
        
        // If center points are provided, adjust transform to zoom towards that point
        let startTranslateX = translateX;
        let startTranslateY = translateY;
        
        if (centerX !== null && centerY !== null) {
            const containerRect = mapContainer.getBoundingClientRect();
            const centerPosX = centerX - containerRect.left - translateX;
            const centerPosY = centerY - containerRect.top - translateY;
            
            // Calculate new translation to keep the point under cursor
            const endTranslateX = translateX - (centerPosX * (targetZoom - zoomLevel)) / startZoom;
            const endTranslateY = translateY - (centerPosY * (targetZoom - zoomLevel)) / startZoom;
            
            function animateZoomWithPan(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3); // easeOutCubic
                
                zoomLevel = startZoom + (change * easeProgress);
                translateX = startTranslateX + (endTranslateX - startTranslateX) * easeProgress;
                translateY = startTranslateY + (endTranslateY - startTranslateY) * easeProgress;
                
                updateTransform();
                
                if (progress < 1) {
                    requestAnimationFrame(animateZoomWithPan);
                }
            }
            
            requestAnimationFrame(animateZoomWithPan);
        } else {
            function animateZoom(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3); // easeOutCubic
                
                zoomLevel = startZoom + (change * easeProgress);
                updateTransform();
                
                if (progress < 1) {
                    requestAnimationFrame(animateZoom);
                }
            }
            
            requestAnimationFrame(animateZoom);
        }
    }
    
    // Button event handlers
    zoomInBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (zoomLevel < maxZoom) {
            smoothZoom(Math.min(zoomLevel + zoomStep, maxZoom));
        }
    });
    
    zoomOutBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (zoomLevel > minZoom) {
            smoothZoom(Math.max(zoomLevel - zoomStep, minZoom));
        }
    });
    
    resetZoomBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        translateX = 0;
        translateY = 0;
        smoothZoom(1);
    });
    
    // Mouse wheel zoom
    mapContainer.addEventListener('wheel', (e) => {
        e.preventDefault();
        
        const delta = e.deltaY < 0 ? zoomStep : -zoomStep;
        const newZoom = Math.min(Math.max(zoomLevel + delta, minZoom), maxZoom);
        
        if (newZoom !== zoomLevel) {
            smoothZoom(newZoom, e.clientX, e.clientY);
        }
    }, { passive: false });
    
    // Pan start
    mapContainer.addEventListener('mousedown', (e) => {
        // Check if clicked on draggable element
        const isDraggableElement = e.target.closest('.draggable-rack') || e.target.closest('.feature-draggable');
        
        // Only initiate pan if not clicking on a draggable element
        if (!isDraggableElement) {
            e.preventDefault();
            isPanning = true;
            startX = e.clientX;
            startY = e.clientY;
            lastX = startX;
            lastY = startY;
            mapContainer.style.cursor = 'grabbing';
        }
    });
    
    // Pan move
    document.addEventListener('mousemove', (e) => {
        if (!isPanning) return;
        
        const dx = e.clientX - lastX;
        const dy = e.clientY - lastY;
        
        lastX = e.clientX;
        lastY = e.clientY;
        
        translateX += dx;
        translateY += dy;
        
        updateTransform();
    });
    
    // Pan end
    document.addEventListener('mouseup', () => {
        if (isPanning) {
            isPanning = false;
            mapContainer.style.cursor = 'grab';
        }
    });
    
    // Touch events for mobile
    let initialTouchDistance = null;
    
    mapContainer.addEventListener('touchstart', (e) => {
        const isDraggableElement = e.target.closest('.draggable-rack') || e.target.closest('.feature-draggable');
        
        if (e.touches.length === 2) {
            // Zoom with two fingers
            e.preventDefault();
            initialTouchDistance = getTouchDistance(e.touches);
        } else if (e.touches.length === 1 && !isDraggableElement) {
            // Pan with one finger if not touching a draggable element
            isPanning = true;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            lastX = startX;
            lastY = startY;
        }
    }, { passive: false });
    
    mapContainer.addEventListener('touchmove', (e) => {
        if (e.touches.length === 2 && initialTouchDistance !== null) {
            // Zoom gesture
            e.preventDefault();
            const currentDistance = getTouchDistance(e.touches);
            const scale = currentDistance / initialTouchDistance;
            
            // Calculate center point between the two touches
            const centerX = (e.touches[0].clientX + e.touches[1].clientX) / 2;
            const centerY = (e.touches[0].clientY + e.touches[1].clientY) / 2;
            
            const newZoom = Math.min(Math.max(zoomLevel * scale, minZoom), maxZoom);
            
            if (Math.abs(newZoom - zoomLevel) > 0.01) {
                smoothZoom(newZoom, centerX, centerY);
                initialTouchDistance = currentDistance; // Update for smoother zooming
            }
        } else if (isPanning && e.touches.length === 1) {
            // Pan gesture
            e.preventDefault();
            const dx = e.touches[0].clientX - lastX;
            const dy = e.touches[0].clientY - lastY;
            
            lastX = e.touches[0].clientX;
            lastY = e.touches[0].clientY;
            
            translateX += dx;
            translateY += dy;
            updateTransform();
        }
    }, { passive: false });
    
    mapContainer.addEventListener('touchend', () => {
        initialTouchDistance = null;
        isPanning = false;
    });
    
    function getTouchDistance(touches) {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }
    
    // Initialize
    updateTransform();
    mapContainer.style.cursor = 'grab';
    
    // Draggable rack implementation
    const rackMarker = document.getElementById('rack-position-marker-' + mapId);
    const rackLabel = document.getElementById('rack-label-' + mapId);
    const positionX = document.getElementById('position_x-' + mapId);
    const positionY = document.getElementById('position_y-' + mapId);
    const positionDisplay = document.getElementById('position-display-' + mapId);
    
    if (rackMarker && (positionX || positionY)) {
        // Add draggable class to rack marker for clear identification
        rackMarker.classList.add('draggable-rack');
        
        let isDragging = false;
        let offsetX, offsetY;
        
        // Initialize drag events
        rackMarker.addEventListener('mousedown', startDrag);
        
        function startDrag(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent panning
            
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
            
            // Get the container and map content dimensions
            const containerRect = mapContainer.getBoundingClientRect();
            
            // Convert client coordinates to map content coordinates (considering zoom and pan)
            const mapContentX = (e.clientX - containerRect.left - translateX) / zoomLevel;
            const mapContentY = (e.clientY - containerRect.top - translateY) / zoomLevel;
            
            // Calculate new position with offset
            let newLeft = mapContentX - offsetX / zoomLevel;
            let newTop = mapContentY - offsetY / zoomLevel;
            
            // Constrain to map boundaries
            const mapWidth = parseFloat(window.getComputedStyle(mapContent).width);
            const mapHeight = parseFloat(window.getComputedStyle(mapContent).height);
            
            newLeft = Math.min(Math.max(newLeft, 0), mapWidth - 40);
            newTop = Math.min(Math.max(newTop, 0), mapHeight - 40);
            
            // Snap to grid (50px)
            newLeft = Math.round(newLeft / 50) * 50;
            newTop = Math.round(newTop / 50) * 50;
            
            // Update marker position
            rackMarker.style.left = newLeft + 'px';
            rackMarker.style.top = newTop + 'px';
            
            // Update label position if it exists
            if (rackLabel) {
                rackLabel.style.left = newLeft + 'px';
                rackLabel.style.top = (newTop + 45) + 'px';
            }
            
            // Update hidden inputs
            if (positionX) positionX.value = newLeft;
            if (positionY) positionY.value = newTop;
            
            // Update position display
            if (positionDisplay) {
                positionDisplay.textContent = `X: ${newLeft}, Y: ${newTop}`;
            }
        }
        
        function stopDrag() {
            isDragging = false;
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('mouseup', stopDrag);
        }
        
        // Touch support for mobile devices
        rackMarker.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = rackMarker.getBoundingClientRect();
            offsetX = touch.clientX - rect.left;
            offsetY = touch.clientY - rect.top;
            
            isDragging = true;
            document.addEventListener('touchmove', touchDrag);
            document.addEventListener('touchend', touchEnd);
        });
        
        function touchDrag(e) {
            if (!isDragging) return;
            
            e.preventDefault();
            const touch = e.touches[0];
            
            // Get the container and map content dimensions
            const containerRect = mapContainer.getBoundingClientRect();
            
            // Convert touch coordinates to map content coordinates (considering zoom and pan)
            const mapContentX = (touch.clientX - containerRect.left - translateX) / zoomLevel;
            const mapContentY = (touch.clientY - containerRect.top - translateY) / zoomLevel;
            
            // Calculate new position with offset
            let newLeft = mapContentX - offsetX / zoomLevel;
            let newTop = mapContentY - offsetY / zoomLevel;
            
            // Constrain to map boundaries
            const mapWidth = parseFloat(window.getComputedStyle(mapContent).width);
            const mapHeight = parseFloat(window.getComputedStyle(mapContent).height);
            
            newLeft = Math.min(Math.max(newLeft, 0), mapWidth - 40);
            newTop = Math.min(Math.max(newTop, 0), mapHeight - 40);
            
            // Snap to grid (50px)
            newLeft = Math.round(newLeft / 50) * 50;
            newTop = Math.round(newTop / 50) * 50;
            
            // Update marker position
            rackMarker.style.left = newLeft + 'px';
            rackMarker.style.top = newTop + 'px';
            
            // Update label position if it exists
            if (rackLabel) {
                rackLabel.style.left = newLeft + 'px';
                rackLabel.style.top = (newTop + 45) + 'px';
            }
            
            // Update hidden inputs
            if (positionX) positionX.value = newLeft;
            if (positionY) positionY.value = newTop;
            
            // Update position display
            if (positionDisplay) {
                positionDisplay.textContent = `X: ${newLeft}, Y: ${newTop}`;
            }
        }
        
        function touchEnd() {
            isDragging = false;
            document.removeEventListener('touchmove', touchDrag);
            document.removeEventListener('touchend', touchEnd);
        }
    }
}


</script>
<?php
}
?>