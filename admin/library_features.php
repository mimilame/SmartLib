<?php 
/**
 * Library Features Component
 * Displays a consistent map with library features and existing racks
 * 
 * @param string $mode View mode ('view', 'edit', 'add', or 'list')
 * @param array $feature Optional feature data for edit/view modes
 * @param array $allRacks Optional array of all racks to display on the map
 * @param array $allFeatures Optional array of all features for list mode
 * @param string $mapSize Optional map size ('very_small', 'small', 'medium', 'large')
 * @param bool $showControls Optional parameter to show control buttons
 */
function renderLibraryFeatures($mode = 'list', $feature = null, $allRacks = [], $allFeatures = [], $mapSize = 'medium', $showControls = true) {
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
    if (is_numeric($mapSize)) {
        // Handle numeric input as height (backward compatibility)
        $mapHeight = intval($mapSize);
        $mapWidth = intval($mapSize * 1.2); // Approximate aspect ratio
        $sizeKey = 'custom';
        $sizeUnits = ['Custom', 'Custom'];
    } else {
        $sizeKey = isset($mapSizes[$mapSize]) ? $mapSize : 'medium';
        $mapWidth = $mapSizes[$sizeKey]['width'];
        $mapHeight = $mapSizes[$sizeKey]['height'];
        $sizeUnits = $mapSizes[$sizeKey]['units'];
    }
    
    // Default positions if not provided
    $position_x = isset($feature['position_x']) ? $feature['position_x'] : 100;
    $position_y = isset($feature['position_y']) ? $feature['position_y'] : 100;
    $width = isset($feature['width']) ? $feature['width'] : 100;
    $height = isset($feature['height']) ? $feature['height'] : 60;
    
    // Determine feature colors and styles based on available data
    $bgColor = isset($feature['bg_color']) ? $feature['bg_color'] : 'bg-primary';
    $textColor = isset($feature['text_color']) ? $feature['text_color'] : 'text-white';
    $icon = isset($feature['feature_icon']) ? $feature['feature_icon'] : 'fas fa-bookmark';
    $featureName = isset($feature['feature_name']) ? $feature['feature_name'] : '';
    
    // Determine if feature is draggable
    $isDraggable = ($mode === 'add' || $mode === 'edit');
    $cursor = $isDraggable ? 'move' : 'default';
    $dragBorder = $isDraggable ? 'border: 2px dashed #007bff;' : '';
    $zIndex = $isDraggable ? 'z-index: 100;' : '';
    
    // Create unique ID for map container
    $mapId = 'library-features-' . uniqid();
?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <h6 class="m-0 d-flex align-items-center">
                <i class="fas fa-layer-group me-2 text-primary"></i>
                Library Features Map
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
                <?php if ($mode === 'edit'): ?>
                | <i class="fas fa-expand-arrows-alt me-1"></i>
                Resize handles available
                <?php endif; ?>
            </small>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div id="<?= $mapId ?>" class="feature-map-container position-relative overflow-hidden" style="height: <?= $mapHeight ?>px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0;">
            <!-- Map Container with Fixed Dimensions -->
            <div class="library-map" style="width: 100%; height: 100%; overflow: hidden; position: relative;">
                <div id="map-content-<?= $mapId ?>" class="map-content position-absolute" style="width: <?= $mapWidth ?>px; height: <?= $mapHeight ?>px; transform-origin: 0 0; background-image: linear-gradient(#e9ecef 1px, transparent 1px), linear-gradient(90deg, #e9ecef 1px, transparent 1px); background-size: 50px 50px; transition: transform 0.2s ease;">
                    <?php if ($mode === 'list'): ?>
                    <!-- List Mode: Show all features -->
                    <?php foreach ($allFeatures as $listFeature): ?>
                        <?php
                            $listBgColor = isset($listFeature['bg_color']) ? $listFeature['bg_color'] : 'bg-primary';
                            $listTextColor = isset($listFeature['text_color']) ? $listFeature['text_color'] : 'text-white';
                            $listIcon = isset($listFeature['feature_icon']) ? $listFeature['feature_icon'] : 'fas fa-bookmark';
                            $listPositionX = isset($listFeature['position_x']) ? $listFeature['position_x'] : 0;
                            $listPositionY = isset($listFeature['position_y']) ? $listFeature['position_y'] : 0;
                            $listWidth = isset($listFeature['width']) ? $listFeature['width'] : 100;
                            $listHeight = isset($listFeature['height']) ? $listFeature['height'] : 60;
                            $featureId = isset($listFeature['feature_id']) ? $listFeature['feature_id'] : null;
                            $listFeatureName = isset($listFeature['feature_name']) ? $listFeature['feature_name'] : '';
                            
                            // Skip disabled features unless viewing specific one
                            if (isset($listFeature['feature_status']) && $listFeature['feature_status'] !== 'Enable' && 
                                !(isset($feature['feature_id']) && $feature['feature_id'] == $featureId)) {
                                continue;
                            }
                        ?>
                        <a href="<?= $featureId ? "setting.php?tab=features&action=view_feature&code={$featureId}" : "#" ?>" class="text-decoration-none">
                            <div class="position-absolute d-flex justify-content-center align-items-center <?= $listBgColor ?> <?= $listTextColor ?>" 
                                style="width: <?= $listWidth ?>px; height: <?= $listHeight ?>px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <i class="<?= $listIcon ?> me-2"></i>
                                <?= htmlspecialchars($listFeatureName) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <!-- Show All Racks (As Context) -->
                    <?php foreach ($allRacks as $listRack): ?>
                        <?php
                            // Skip disabled racks
                            if (isset($listRack['location_rack_status']) && $listRack['location_rack_status'] !== 'Enable') {
                                continue;
                            }
                            
                            $listPositionX = isset($listRack['position_x']) ? $listRack['position_x'] : 0;
                            $listPositionY = isset($listRack['position_y']) ? $listRack['position_y'] : 0;
                            $rackName = isset($listRack['location_rack_name']) ? $listRack['location_rack_name'] : '';
                        ?>
                        <div class="position-absolute d-flex justify-content-center align-items-center bg-secondary text-white opacity-75" 
                            style="width: 40px; height: 40px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fas fa-archive"></i>
                        </div>
                        
                        <!-- Rack labels -->
                        <?php if ($rackName): ?>
                        <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label opacity-75" 
                            style="top: <?= $listPositionY + 45 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 89; white-space: nowrap;">
                            <?= htmlspecialchars($rackName) ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php elseif ($mode === 'add' || $mode === 'edit'): ?>
                    <!-- Add/Edit Mode: Show draggable feature and other features/racks -->
                    
                    <!-- Show Other Features (Secondary) -->
                    <?php foreach ($allFeatures as $listFeature): ?>  
                        <?php
                            // Skip the current feature being edited
                            if ($mode === 'edit' && isset($feature['feature_id']) && isset($listFeature['feature_id']) && $listFeature['feature_id'] == $feature['feature_id']) {
                                continue;
                            }
                            
                            // Skip disabled features
                            if (isset($listFeature['feature_status']) && $listFeature['feature_status'] !== 'Enable') {
                                continue;
                            }
                            
                            $listBgColor = isset($listFeature['bg_color']) ? $listFeature['bg_color'] : 'bg-primary';
                            $listTextColor = isset($listFeature['text_color']) ? $listFeature['text_color'] : 'text-white';
                            $listIcon = isset($listFeature['feature_icon']) ? $listFeature['feature_icon'] : 'fas fa-bookmark';
                            $listPositionX = isset($listFeature['position_x']) ? $listFeature['position_x'] : 0;
                            $listPositionY = isset($listFeature['position_y']) ? $listFeature['position_y'] : 0;
                            $listWidth = isset($listFeature['width']) ? $listFeature['width'] : 100;
                            $listHeight = isset($listFeature['height']) ? $listFeature['height'] : 60;
                            $listFeatureName = isset($listFeature['feature_name']) ? $listFeature['feature_name'] : '';
                        ?>
                        <div class="position-absolute d-flex justify-content-center align-items-center <?= $listBgColor ?> <?= $listTextColor ?> opacity-75" 
                            style="width: <?= $listWidth ?>px; height: <?= $listHeight ?>px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="<?= $listIcon ?> me-2"></i>
                            <?= htmlspecialchars($listFeatureName) ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Show All Racks (As Context) -->
                    <?php foreach ($allRacks as $listRack): ?>
                        <?php
                            // Skip disabled racks
                            if (isset($listRack['location_rack_status']) && $listRack['location_rack_status'] !== 'Enable') {
                                continue;
                            }
                            
                            $listPositionX = isset($listRack['position_x']) ? $listRack['position_x'] : 0;
                            $listPositionY = isset($listRack['position_y']) ? $listRack['position_y'] : 0;
                            $rackName = isset($listRack['location_rack_name']) ? $listRack['location_rack_name'] : '';
                        ?>
                        <div class="position-absolute d-flex justify-content-center align-items-center bg-secondary text-white opacity-75" 
                            style="width: 40px; height: 40px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fas fa-archive"></i>
                        </div>
                        
                        <!-- Rack labels -->
                        <?php if ($rackName): ?>
                        <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label opacity-75" 
                            style="top: <?= $listPositionY + 45 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 89; white-space: nowrap;">
                            <?= htmlspecialchars($rackName) ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <!-- Draggable Feature Element (Emphasized) -->
                    <div id="feature-position-marker-<?= $mapId ?>" 
                        class="position-absolute d-flex justify-content-center align-items-center <?= $bgColor ?> <?= $textColor ?> fw-bold feature-draggable" 
                        style="width: <?= $width ?>px; height: <?= $height ?>px; cursor: <?= $cursor ?>; border-radius: 4px; top: <?= $position_y ?>px; left: <?= $position_x ?>px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); <?= $dragBorder ?> <?= $zIndex ?>">
                        <i class="<?= $icon ?> me-2"></i>
                        <?= htmlspecialchars($featureName) ?>
                        
                        <?php if ($mode === 'edit'): ?>
                        <!-- Resize handles -->
                        <div class="resize-handle resize-handle-se position-absolute" style="width: 10px; height: 10px; background-color: #fff; border: 1px solid #007bff; bottom: -5px; right: -5px; cursor: se-resize; border-radius: 50%;"></div>
                        <div class="resize-handle resize-handle-sw position-absolute" style="width: 10px; height: 10px; background-color: #fff; border: 1px solid #007bff; bottom: -5px; left: -5px; cursor: sw-resize; border-radius: 50%;"></div>
                        <div class="resize-handle resize-handle-ne position-absolute" style="width: 10px; height: 10px; background-color: #fff; border: 1px solid #007bff; top: -5px; right: -5px; cursor: ne-resize; border-radius: 50%;"></div>
                        <div class="resize-handle resize-handle-nw position-absolute" style="width: 10px; height: 10px; background-color: #fff; border: 1px solid #007bff; top: -5px; left: -5px; cursor: nw-resize; border-radius: 50%;"></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php elseif ($mode === 'view'): ?>
                    <!-- View Mode: Show single feature in primary while other features and racks with secondary styling -->
                    
                    <!-- Show all racks -->
                    <?php foreach ($allRacks as $listRack): ?>
                        <?php
                            // Skip disabled racks
                            if (isset($listRack['location_rack_status']) && $listRack['location_rack_status'] !== 'Enable') {
                                continue;
                            }
                            
                            $listPositionX = isset($listRack['position_x']) ? $listRack['position_x'] : 0;
                            $listPositionY = isset($listRack['position_y']) ? $listRack['position_y'] : 0;
                            $rackId = isset($listRack['location_rack_id']) ? $listRack['location_rack_id'] : null;
                            $rackName = isset($listRack['location_rack_name']) ? $listRack['location_rack_name'] : '';
                        ?>
                        <a href="<?= $rackId ? "location_rack.php?action=view&code={$rackId}" : "#" ?>" class="text-decoration-none">
                            <div class="position-absolute d-flex justify-content-center align-items-center bg-secondary text-white opacity-75" 
                                style="width: 40px; height: 40px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <i class="fas fa-archive"></i>
                            </div>
                            
                            <!-- Rack labels -->
                            <?php if ($rackName): ?>
                            <div class="position-absolute bg-dark text-white px-2 py-1 rounded rack-label opacity-75" 
                                style="top: <?= $listPositionY + 45 ?>px; left: <?= $listPositionX ?>px; transform: translateX(-25%); font-size: 0.75rem; z-index: 89; white-space: nowrap;">
                                <?= htmlspecialchars($rackName) ?>
                            </div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Show other features -->
                    <?php foreach ($allFeatures as $listFeature): ?>
                        <?php
                            // Skip the current feature being viewed
                            if (isset($feature['feature_id']) && isset($listFeature['feature_id']) && $listFeature['feature_id'] == $feature['feature_id']) {
                                continue;
                            }
                            
                            // Skip disabled features
                            if (isset($listFeature['feature_status']) && $listFeature['feature_status'] !== 'Enable') {
                                continue;
                            }
                            
                            $listBgColor = isset($listFeature['bg_color']) ? $listFeature['bg_color'] : 'bg-primary';
                            $listTextColor = isset($listFeature['text_color']) ? $listFeature['text_color'] : 'text-white';
                            $listIcon = isset($listFeature['feature_icon']) ? $listFeature['feature_icon'] : 'fas fa-bookmark';
                            $listPositionX = isset($listFeature['position_x']) ? $listFeature['position_x'] : 0;
                            $listPositionY = isset($listFeature['position_y']) ? $listFeature['position_y'] : 0;
                            $listWidth = isset($listFeature['width']) ? $listFeature['width'] : 100;
                            $listHeight = isset($listFeature['height']) ? $listFeature['height'] : 60;
                            $featureId = isset($listFeature['feature_id']) ? $listFeature['feature_id'] : null;
                            $listFeatureName = isset($listFeature['feature_name']) ? $listFeature['feature_name'] : '';
                        ?>
                        <a href="<?= $featureId ? "setting.php?tab=features&action=view_feature&code={$featureId}" : "#" ?>" class="text-decoration-none">
                            <div class="position-absolute d-flex justify-content-center align-items-center <?= $listBgColor ?> <?= $listTextColor ?> opacity-75" 
                                style="width: <?= $listWidth ?>px; height: <?= $listHeight ?>px; border-radius: 4px; top: <?= $listPositionY ?>px; left: <?= $listPositionX ?>px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <i class="<?= $listIcon ?> me-2"></i>
                                <?= htmlspecialchars($listFeatureName) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Current feature being viewed (highlighted) -->
                    <div id="static-feature-<?= $mapId ?>" 
                        class="position-absolute d-flex justify-content-center align-items-center <?= $bgColor ?> <?= $textColor ?>" 
                        style="width: <?= $width ?>px; height: <?= $height ?>px; cursor: <?= $cursor ?>; border-radius: 4px; top: <?= $position_y ?>px; left: <?= $position_x ?>px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); z-index: 91;">
                        <i class="<?= $icon ?> me-2"></i>
                        <?= htmlspecialchars($featureName) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($showControls): ?>
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
            <?php endif; ?>
            
            <?php if ($isDraggable): ?>
            <!-- Position and Size Display -->
            <div class="position-absolute start-0 bottom-0 m-2 p-1 bg-white rounded shadow-sm">
                <small class="text-muted">
                    Position: <span id="position-display-<?= $mapId ?>">X: <?= $position_x ?>, Y: <?= $position_y ?></span>
                    <?php if ($mode === 'edit'): ?>
                    | Size: <span id="size-display-<?= $mapId ?>">W: <?= $width ?>, H: <?= $height ?></span>
                    <?php endif; ?>
                </small>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($isDraggable): ?>
    <!-- Hidden inputs for position and dimensions (only in editable modes) -->
    <input type="hidden" name="position_x" id="position_x-<?= $mapId ?>" value="<?= $position_x ?>">
    <input type="hidden" name="position_y" id="position_y-<?= $mapId ?>" value="<?= $position_y ?>">
    <input type="hidden" name="width" id="width-<?= $mapId ?>" value="<?= $width ?>">
    <input type="hidden" name="height" id="height-<?= $mapId ?>" value="<?= $height ?>">
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to initialize all library feature maps on the page
    initializeAllFeatureMaps();
});

function initializeAllFeatureMaps() {
    // Find all feature map containers on the page
    const featureMapContainers = document.querySelectorAll('[id^="library-features-"]');
    
    featureMapContainers.forEach(container => {
        const mapId = container.id;
        initializeFeatureMap(mapId);
    });
}

function initializeFeatureMap(mapId) {
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
    
    // Button event handlers (if buttons exist)
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
        // Check if clicked on draggable element or resize handle
        const isDraggableElement = e.target.closest('.feature-draggable') || e.target.closest('.resize-handle');
        
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
        const isDraggableElement = e.target.closest('.feature-draggable') || e.target.closest('.resize-handle');
        
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
            initialTouchDistance = currentDistance;
            
            // Apply smooth zoom centered on the midpoint between touches
            smoothZoom(newZoom, centerX, centerY);
        } else if (e.touches.length === 1 && isPanning) {
            // Pan gesture
            const dx = e.touches[0].clientX - lastX;
            const dy = e.touches[0].clientY - lastY;
            
            lastX = e.touches[0].clientX;
            lastY = e.touches[0].clientY;
            
            translateX += dx;
            translateY += dy;
            
            updateTransform();
        }
    }, { passive: false });
    
    mapContainer.addEventListener('touchend', (e) => {
        if (e.touches.length < 2) {
            initialTouchDistance = null;
        }
        
        if (e.touches.length === 0) {
            isPanning = false;
        }
    });
    
    function getTouchDistance(touches) {
        return Math.hypot(
            touches[0].clientX - touches[1].clientX,
            touches[0].clientY - touches[1].clientY
        );
    }
    
    // Set initial cursor style
    mapContainer.style.cursor = 'grab';
    
    // Initialize draggable feature (if exists)
    const featureElement = document.getElementById('feature-position-marker-' + mapId);
    if (featureElement) {
        initializeDraggableFeature(featureElement, mapId);
    }
}

function initializeDraggableFeature(element, mapId) {
    const posXInput = document.getElementById('position_x-' + mapId);
    const posYInput = document.getElementById('position_y-' + mapId);
    const widthInput = document.getElementById('width-' + mapId);
    const heightInput = document.getElementById('height-' + mapId);
    const positionDisplay = document.getElementById('position-display-' + mapId);
    const sizeDisplay = document.getElementById('size-display-' + mapId);
    
    // Initial values
    let pos = { x: parseInt(posXInput.value), y: parseInt(posYInput.value) };
    let size = { 
        width: widthInput ? parseInt(widthInput.value) : parseInt(element.style.width),
        height: heightInput ? parseInt(heightInput.value) : parseInt(element.style.height)
    };
    
    let isDragging = false;
    let isResizing = false;
    let resizeDirection = '';
    let startPos = { x: 0, y: 0 };
    let startSize = { width: 0, height: 0 };
    let startCursor = { x: 0, y: 0 };
    
    // Mouse events for dragging
    element.addEventListener('mousedown', function(e) {
        // Check if clicked on a resize handle
        const resizeHandle = e.target.closest('.resize-handle');
        
        if (resizeHandle) {
            // Start resizing
            e.preventDefault();
            isResizing = true;
            
            // Determine which handle was clicked
            if (resizeHandle.classList.contains('resize-handle-se')) {
                resizeDirection = 'se';
            } else if (resizeHandle.classList.contains('resize-handle-sw')) {
                resizeDirection = 'sw';
            } else if (resizeHandle.classList.contains('resize-handle-ne')) {
                resizeDirection = 'ne';
            } else if (resizeHandle.classList.contains('resize-handle-nw')) {
                resizeDirection = 'nw';
            }
            
            startCursor = { x: e.clientX, y: e.clientY };
            startSize = { width: size.width, height: size.height };
            startPos = { x: pos.x, y: pos.y };
        } else {
            // Start dragging (if not clicked on a resize handle)
            e.preventDefault();
            isDragging = true;
            startCursor = { x: e.clientX, y: e.clientY };
            startPos = { x: pos.x, y: pos.y };
        }
    });
    
    document.addEventListener('mousemove', function(e) {
        if (isDragging) {
            // Calculate the change
            const dx = e.clientX - startCursor.x;
            const dy = e.clientY - startCursor.y;
            
            // Apply the transformation based on the current zoom level
            const mapContent = document.getElementById('map-content-' + mapId);
            const transform = window.getComputedStyle(mapContent).transform;
            const matrix = new DOMMatrix(transform);
            const scale = matrix.a; // Current scale factor
            
            // Update position (accounting for zoom)
            pos.x = Math.max(0, startPos.x + Math.round(dx / scale));
            pos.y = Math.max(0, startPos.y + Math.round(dy / scale));
            
            // Update element style
            element.style.left = pos.x + 'px';
            element.style.top = pos.y + 'px';
            
            // Update form inputs
            if (posXInput) posXInput.value = pos.x;
            if (posYInput) posYInput.value = pos.y;
            
            // Update display
            if (positionDisplay) {
                positionDisplay.textContent = `X: ${pos.x}, Y: ${pos.y}`;
            }
        } else if (isResizing) {
            // Calculate the change
            const dx = e.clientX - startCursor.x;
            const dy = e.clientY - startCursor.y;
            
            // Apply the transformation based on the current zoom level
            const mapContent = document.getElementById('map-content-' + mapId);
            const transform = window.getComputedStyle(mapContent).transform;
            const matrix = new DOMMatrix(transform);
            const scale = matrix.a; // Current scale factor
            
            // Handle different resize directions
            const scaledDx = Math.round(dx / scale);
            const scaledDy = Math.round(dy / scale);
            
            // Minimum size constraints
            const minWidth = 60;
            const minHeight = 40;
            
            switch (resizeDirection) {
                case 'se':
                    // Southeast - resize width and height
                    size.width = Math.max(minWidth, startSize.width + scaledDx);
                    size.height = Math.max(minHeight, startSize.height + scaledDy);
                    break;
                case 'sw':
                    // Southwest - resize width (left side) and height
                    const newWidthSw = Math.max(minWidth, startSize.width - scaledDx);
                    pos.x = startPos.x - (newWidthSw - startSize.width);
                    size.width = newWidthSw;
                    size.height = Math.max(minHeight, startSize.height + scaledDy);
                    break;
                case 'ne':
                    // Northeast - resize width and height (top side)
                    size.width = Math.max(minWidth, startSize.width + scaledDx);
                    const newHeightNe = Math.max(minHeight, startSize.height - scaledDy);
                    pos.y = startPos.y - (newHeightNe - startSize.height);
                    size.height = newHeightNe;
                    break;
                case 'nw':
                    // Northwest - resize width (left side) and height (top side)
                    const newWidthNw = Math.max(minWidth, startSize.width - scaledDx);
                    pos.x = startPos.x - (newWidthNw - startSize.width);
                    size.width = newWidthNw;
                    const newHeightNw = Math.max(minHeight, startSize.height - scaledDy);
                    pos.y = startPos.y - (newHeightNw - startSize.height);
                    size.height = newHeightNw;
                    break;
            }
            
            // Update element style
            element.style.width = size.width + 'px';
            element.style.height = size.height + 'px';
            element.style.left = pos.x + 'px';
            element.style.top = pos.y + 'px';
            
            // Update form inputs
            if (widthInput) widthInput.value = size.width;
            if (heightInput) heightInput.value = size.height;
            if (posXInput) posXInput.value = pos.x;
            if (posYInput) posYInput.value = pos.y;
            
            // Update displays
            if (sizeDisplay) {
                sizeDisplay.textContent = `W: ${size.width}, H: ${size.height}`;
            }
            if (positionDisplay) {
                positionDisplay.textContent = `X: ${pos.x}, Y: ${pos.y}`;
            }
        }
    });
    
    document.addEventListener('mouseup', function() {
        isDragging = false;
        isResizing = false;
    });
    
    // Touch events for mobile
    element.addEventListener('touchstart', function(e) {
        if (e.touches.length === 1) {
            e.preventDefault();
            isDragging = true;
            startCursor = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            startPos = { x: pos.x, y: pos.y };
        }
    }, { passive: false });
    
    document.addEventListener('touchmove', function(e) {
        if (isDragging && e.touches.length === 1) {
            // Calculate the change
            const dx = e.touches[0].clientX - startCursor.x;
            const dy = e.touches[0].clientY - startCursor.y;
            
            // Apply the transformation based on the current zoom level
            const mapContent = document.getElementById('map-content-' + mapId);
            const transform = window.getComputedStyle(mapContent).transform;
            const matrix = new DOMMatrix(transform);
            const scale = matrix.a; // Current scale factor
            
            // Update position (accounting for zoom)
            pos.x = Math.max(0, startPos.x + Math.round(dx / scale));
            pos.y = Math.max(0, startPos.y + Math.round(dy / scale));
            
            // Update element style
            element.style.left = pos.x + 'px';
            element.style.top = pos.y + 'px';
            
            // Update form inputs
            if (posXInput) posXInput.value = pos.x;
            if (posYInput) posYInput.value = pos.y;
            
            // Update display
            if (positionDisplay) {
                positionDisplay.textContent = `X: ${pos.x}, Y: ${pos.y}`;
            }
        }
    }, { passive: false });
    
    document.addEventListener('touchend', function() {
        isDragging = false;
    });
}
</script>
<?php
}
?>