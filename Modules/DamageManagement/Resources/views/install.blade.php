<!-- Installation Modal -->
<div class="modal fade" id="install_modal" tabindex="-1" role="dialog" aria-labelledby="install_modal_label">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="install_modal_label">
                    <i class="fa fa-cog"></i> Install Damage Management Module
                </h4>
            </div>
            
            <div class="modal-body">
                <div class="install-content" id="install_content">
                    <!-- License Agreement -->
                    <div id="license_section">
                        <div class="alert alert-info">
                            <h4><i class="fa fa-info-circle"></i> Non-Commercial Use License</h4>
                            <p><strong>Damage Management Module v1.0.0</strong></p>
                            <p><strong>Author:</strong> Hackermiind</p>
                            <p><strong>License:</strong> Complete free module for non commercial use</p>
                        </div>
                        
                        <div class="license-agreement" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                            <h5>License Terms and Conditions</h5>
                            <p><strong>1. Grant of License</strong></p>
                            <p>This software module ("Software") is provided by Hackermiind ("Author") free of charge for non-commercial use only.</p>
                            
                            <p><strong>2. Non-Commercial Use</strong></p>
                            <p>This module is intended for use in non-commercial, educational, personal, or non-profit projects only. You may not use this module in any commercial application without explicit written permission from the author.</p>
                            
                            <p><strong>3. Distribution</strong></p>
                            <p>You may not redistribute, sell, lease, or sublicense this module to any third party. The module must remain with the original system.</p>
                            
                            <p><strong>4. Modifications</strong></p>
                            <p>You may modify this module for your own use, but you must retain all copyright notices and author information.</p>
                            
                            <p><strong>5. Warranty</strong></p>
                            <p>THIS SOFTWARE IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED. THE AUTHOR SHALL NOT BE LIABLE FOR ANY DAMAGES ARISING FROM THE USE OF THIS SOFTWARE.</p>
                            
                            <p><strong>6. Support</strong></p>
                            <p>This is a free module and comes with no warranty or support obligation. The author is not obligated to provide any technical support, maintenance, or updates.</p>
                            
                            <p><strong>7. Termination</strong></p>
                            <p>Your license to use this module will automatically terminate if you violate any of the terms of this agreement.</p>
                            
                            <p><strong>8. Acknowledgement</strong></p>
                            <p>By using this module, you acknowledge that you have read this agreement, understand it, and agree to be bound by its terms and conditions.</p>
                        </div>
                        
                        <div class="checkbox" style="margin-top: 20px;">
                            <label>
                                <input type="checkbox" id="license_agreed" name="license_agreed">
                                <strong>I have read and agree to the terms and conditions stated above</strong>
                            </label>
                        </div>
                    </div>

                    <!-- Installation Progress (Hidden Initially) -->
                    <div id="install_section" style="display: none;">
                        <div class="text-center">
                            <i class="fa fa-cog fa-spin fa-3x text-primary"></i>
                            <h3>Installing Module...</h3>
                            <p class="text-muted">Please wait while we install the Damage Management Module</p>
                        </div>
                        
                        <div class="progress" style="height: 30px; margin: 20px 0;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 id="install_progress" 
                                 style="width: 0%">
                                <span id="progress_text">0%</span>
                            </div>
                        </div>
                        
                        <div id="install_status" class="alert alert-info" style="margin-top: 15px;">
                            <i class="fa fa-info-circle"></i> Preparing installation...
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="btn_cancel">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btn_install" disabled>
                    <i class="fa fa-download"></i> Install
                </button>
            </div>
        </div>
    </div>
</div>

<style>
#license_section, #install_section {
    min-height: 300px;
}
.license-agreement p {
    margin: 10px 0;
    font-size: 13px;
    line-height: 1.6;
}
#install_steps li.active a {
    background-color: #3c8dbc;
    color: white;
}
#install_steps li.disabled a {
    color: #999;
    cursor: not-allowed;
}
</style>

<script type="text/javascript">
// Wait for jQuery to be available
(function() {
    function initInstallModal() {
        if (typeof jQuery === 'undefined') {
            console.log('jQuery not loaded, waiting...');
            setTimeout(initInstallModal, 100);
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            var installationStarted = false;

            console.log('Install modal script loaded');

            // Modal open - reset state
            $('#install_modal').on('show.bs.modal', function() {
                $('#license_agreed').prop('checked', false);
                $('#btn_install').prop('disabled', true);
                $('#btn_cancel').show();
                $('#license_section').show();
                $('#install_section').hide();
                installationStarted = false;
            });

            // Checkbox handler - enable/disable install button
            $('#license_agreed').on('change', function() {
                var isChecked = this.checked;
                console.log('Checkbox changed:', isChecked);
                $('#btn_install').prop('disabled', !isChecked);
            });

            // Install button click handler
            $(document).on('click', '#btn_install', function(e) {
                e.preventDefault();
                
                console.log('Install button clicked');
                
                var isChecked = $('#license_agreed').is(':checked');
                console.log('License checked:', isChecked);
                
                if (!isChecked) {
                    console.log('License not agreed');
                    alert('Please agree to the license terms first');
                    return false;
                }
                
                console.log('Starting installation');
                
                // Disable button
                $(this).prop('disabled', true);
                
                // Hide license section, show installation section
                $('#license_section').fadeOut(function() {
                    $('#install_section').fadeIn();
                });
                $('#btn_cancel').hide();
                
                // Start installation
                startInstallation();
                
                return false;
            });

            function startInstallation() {
                if (installationStarted) {
                    console.log('Installation already started');
                    return;
                }
                installationStarted = true;
                console.log('Starting installation function');

                var progress = 0;
                var installUrl = '{{ action([\Modules\DamageManagement\Http\Controllers\InstallController::class, "index"]) }}';
                console.log('Install URL:', installUrl);

                // Simulate installation progress
                var progressInterval = setInterval(function() {
                    progress += 10;
                    if (progress <= 100) {
                        $('#install_progress').css('width', progress + '%');
                        $('#progress_text').text(progress + '%');
                        
                        if (progress === 20) {
                            $('#install_status').html('<i class="fa fa-database"></i> Checking database...');
                        } else if (progress === 40) {
                            $('#install_status').html('<i class="fa fa-table"></i> Creating tables...');
                        } else if (progress === 60) {
                            $('#install_status').html('<i class="fa fa-key"></i> Setting up indexes...');
                        } else if (progress === 80) {
                            $('#install_status').html('<i class="fa fa-check"></i> Installing migrations...');
                        } else if (progress === 100) {
                            $('#install_status').html('<i class="fa fa-check-circle text-success"></i> Installation complete!');
                            clearInterval(progressInterval);
                            
                            console.log('Making AJAX call');
                            // Make actual AJAX call to install
                            $.ajax({
                                url: installUrl,
                                type: 'GET',
                                success: function(response) {
                                    console.log('Installation successful');
                                    setTimeout(function() {
                                        $('#install_status').html('<div class="alert alert-success"><h4><i class="fa fa-check-circle"></i> Installation Complete!</h4><p>The Damage Management Module has been successfully installed.</p><p><strong>Next Steps:</strong></p><ul><li>Configure module permissions for your users</li><li>Navigate to <strong>Damage Management</strong> menu to start tracking</li><li>Review the module documentation</li></ul></div>');
                                        
                                        var $closeBtn = $('<button>', {
                                            type: 'button',
                                            class: 'btn btn-success btn-block',
                                            text: 'Close',
                                            click: function() {
                                                window.location.reload();
                                            }
                                        });
                                        $('#install_status').append($closeBtn);
                                    }, 500);
                                },
                                error: function(xhr) {
                                    console.log('Installation error:', xhr.responseText);
                                    $('#install_status').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> Installation failed: ' + xhr.responseText + '</div>');
                                    var $retryBtn = $('<button>', {
                                        type: 'button',
                                        class: 'btn btn-danger',
                                        text: 'Close',
                                        click: function() {
                                            $('#install_modal').modal('hide');
                                        }
                                    });
                                    $('#install_status').append($retryBtn);
                                }
                            });
                        }
                    }
                }, 300);
            }
        });
    }
    
    // Start initialization
    initInstallModal();
})();
</script>

