jQuery(document).ready(function($) {
    $("#autocomplete_product").autocomplete({
        source: function(req, response){
            // var $tax_id     = $('input[name="taxonomy_id"]').val();
            $.getJSON(AJAX.ajax_url+'?callback=?&action=autocomplete_action', req, response);
        },
        create: function() {
            $(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
                console.log(item);
                return $( "<li>" ).append( "<div>" + item.product + "</div>" ).appendTo( ul );
            }
        },
        select: function(event, ui) {
            console.log(ui);
            // $(".owner_country").append("<span data-first_price=" + ui.item.first_price + " data-next_price=" + ui.item.next_price + " >" + ui.item.country + "<b class='delete'>x</b></span");
            var product = $('input[name="product"]');

            product.val(ui.item.id);
            $("input#autocomplete_product").hide();
            $('.product_label').html(ui.item.product).show();

            // Neu co truong gia tien thi dien vao luon
            if ($('input[name="price"]').length) {
                $('input[name="price"]').val(ui.item.price);
                $('input[name="single_price"]').val(ui.item.price);
            }
        },
        minLength: 0,
    });

    $('input[type="checkbox"]').click( function () {
        var numberOfChecked = $('input:checkbox:checked').length;
        var single_price = $('input[name="single_price"]').val();

        $.ajax({
            type: "POST",
            url: AJAX.ajax_url,
            data: {
              action: "calculate_price",
              quantity: numberOfChecked,
              price: single_price
            },
            error: function (xhr, ajaxOptions, thrownError) {
              console.log(xhr.status);
              console.log(xhr.responseText);
              console.log(thrownError);
            },
            success: function (resp) {
                $('input[name="price"]').val(resp);
            },
        });
    });

    $('input[name="price"]').change( function() {
        var numberOfChecked = $('input:checkbox:checked').length;
        var total = $(this).val();

        if (numberOfChecked <= 1) {
            $('input[name="single_price"]').val(total);
        } else {
            $('input[name="single_price"]').val(total / numberOfChecked);
        }
    });

    // Initialize datepicker for the date input in create_order.php
    if ($('#datepicker').length) {
        // Load jQuery UI if not already included
        if (typeof $.datepicker === 'undefined') {
            // Check if WordPress admin includes jQuery UI
            if (typeof wp !== 'undefined' && wp.includes) {
                wp.includes.add('jquery-ui-datepicker');
            } else {
                // Fallback - append stylesheets and scripts
                $('head').append('<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">');
                $.getScript('https://code.jquery.com/ui/1.13.2/jquery-ui.js');
            }
        }
        
        // Initialize datepicker with dd/mm/yyyy format
        $('#datepicker').datepicker({
            dateFormat: 'dd/mm/yy', // 'yy' means 4-digit year in jQuery UI
            changeMonth: true,
            changeYear: true,
            firstDay: 1, // Start week on Monday
            dayNamesMin: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
            monthNamesShort: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
            onSelect: function(dateText, inst) {
                updateOrderTitle(dateText);
            }
        });
        
        // Handle order title auto-update
        function updateOrderTitle(dateText) {
            var $orderTitle = $('#order_title');
            var manuallyChanged = $orderTitle.data('manually-changed');
            
            // Only update if not manually changed
            if (manuallyChanged !== true) {
                var groupName = $('#group_name').val();
                if (groupName) {
                    // Convert from dd/mm/yyyy to dd-mm/yyyy format for title
                    var dateParts = dateText.split('/');
                    var formattedDate = dateParts[0] + '-' + dateParts[1] + '-' + dateParts[2];
                    $orderTitle.val(groupName + ' ' + formattedDate);
                }
            }
        }
        
        // Track when user manually changes the title
        $('#order_title').on('input', function() {
            $(this).data('manually-changed', true);
        });
    }

    // Password confirmation validation for edit-user-form
    if ($("#edit-user-form").length) {
        // Function to validate password complexity
        function validatePasswordComplexity(password) {
            var errors = [];
            
            // Check minimum length
            if (password.length < 8) {
                errors.push("Mật khẩu phải có ít nhất 8 ký tự");
            }
            
            // Check for uppercase letter
            if (!/[A-Z]/.test(password)) {
                errors.push("Mật khẩu phải có ít nhất 1 chữ in hoa");
            }
            
            // Check for special character
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                errors.push("Mật khẩu phải có ít nhất 1 ký tự đặc biệt (!@#$%^&*...)");
            }
            
            return errors;
        }
        
        // Function to check if passwords match and validate complexity
        function checkPassword() {
            var password = $("#password").val();
            var confirmPassword = $("#confirm_password").val();
            var $errorElement = $("#password-error");
            
            // Only validate if password field has a value
            if (password.length > 0) {
                // Check password complexity
                var complexityErrors = validatePasswordComplexity(password);
                
                if (complexityErrors.length > 0) {
                    $errorElement.html(complexityErrors.join("<br>"));
                    $errorElement.show();
                    return false;
                }
                
                // Check if passwords match
                if (confirmPassword.length > 0 && password !== confirmPassword) {
                    $errorElement.html("Mật khẩu không khớp");
                    $errorElement.show();
                    return false;
                }
            } else if (confirmPassword.length > 0) {
                // Confirm password has value but password is empty
                $errorElement.html("Vui lòng nhập mật khẩu mới");
                $errorElement.show();
                return false;
            }
            
            // All checks passed
            $errorElement.hide();
            return true;
        }
        
        // Check password when either field changes
        $("#password, #confirm_password").on("keyup", function() {
            checkPassword();
        });
        
        // Validate form on submission
        $("#edit-user-form").on("submit", function(e) {
            var password = $("#password").val();
            
            // If password field has a value, validate it
            if (password.length > 0) {
                if (!checkPassword()) {
                    e.preventDefault(); // Prevent form submission
                    // Scroll to error message
                    $('html, body').animate({
                        scrollTop: $("#password-error").offset().top - 100
                    }, 200);
                }
            }
        });
    }

    // Starry background for login page
    if ($('#starry-background').length) {
        const canvas = document.getElementById('starry-background');
        const ctx = canvas.getContext('2d');
        
        // Stars array - moved declaration to the top of the scope
        let stars = [];
        
        // Set canvas to full window size
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            createStars(); // Recreate stars when resizing
        }
        
        // Handle window resize
        window.addEventListener('resize', resizeCanvas);
        
        // Create stars
        function createStars() {
            stars = []; // Clear existing stars
            
            // Create 200 stars with random properties
            for (let i = 0; i < 200; i++) {
                stars.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    radius: Math.random() * 1.5 + 0.5,
                    opacity: Math.random(),
                    speed: Math.random() * 0.05,
                    blinkSpeed: Math.random() * 0.05,
                    blinkDirection: Math.random() > 0.5 ? 1 : -1
                });
            }
        }
        
        // Initialize the canvas
        resizeCanvas();
        
        // Animation function
        function animate() {
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw background gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
            gradient.addColorStop(0, '#0a1628');
            gradient.addColorStop(1, '#1e3a5f');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Draw each star
            stars.forEach(star => {
                // Update star blinking (opacity change)
                star.opacity += star.blinkSpeed * star.blinkDirection;
                
                // Change direction when opacity reaches limits
                if (star.opacity > 1 || star.opacity < 0.1) {
                    star.blinkDirection *= -1;
                }
                
                // Move star position slightly for twinkling effect
                star.y += star.speed;
                
                // Reset position if star moves out of canvas
                if (star.y > canvas.height) {
                    star.y = 0;
                }
                
                // Draw the star
                ctx.beginPath();
                ctx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${star.opacity})`;
                ctx.fill();
            });
            
            // Continue animation
            requestAnimationFrame(animate);
        }
        
        // Start animation
        animate();
    }

    // Handle instruction menu navigation
    if ($('.instructions-container').length) {
        const $menu = $('.instructions-menu');
        const $menuItems = $menu.find('a');
        const $sections = $('.instruction-section');
        const headerHeight = 80; // Adjust based on your fixed header height
        
        // Handle click on menu items
        $menuItems.on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            const $targetSection = $(target);
            
            if ($targetSection.length) {
                // Update active menu item
                $menuItems.removeClass('active');
                $(this).addClass('active');
                
                // Smooth scroll to section
                $('html, body').animate({
                    scrollTop: $targetSection.offset().top - headerHeight
                }, 500);
                
                // Update URL hash without scrolling
                if (history.pushState) {
                    history.pushState(null, null, target);
                } else {
                    location.hash = target;
                }
            }
        });
        
        // Update active menu item on scroll
        $(window).on('scroll', function() {
            const scrollPosition = $(window).scrollTop() + headerHeight + 50;
            
            $sections.each(function() {
                const $section = $(this);
                const sectionTop = $section.offset().top;
                const sectionBottom = sectionTop + $section.outerHeight();
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                    const sectionId = $section.attr('id');
                    
                    $menuItems.removeClass('active');
                    $menuItems.filter(`[href="#${sectionId}"]`).addClass('active');
                }
            });
        });
        
        // Handle initial hash in URL
        if (location.hash) {
            const $targetMenuItem = $menuItems.filter(`[href="${location.hash}"]`);
            if ($targetMenuItem.length) {
                setTimeout(function() {
                    $targetMenuItem.trigger('click');
                }, 100);
            }
        }
    }
});