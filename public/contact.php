<?php

$pageTitle = 'Contact Us - Store';

require_once __DIR__ . '/../includes/header.php';
?>

    <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact us</li>
            </ol>
        </div>
    </nav>

    <div class="container">
        <div class="page-header page-header-big text-center" style="background-image: url('assets/images/contact-header-bg.jpg')">
            <h1 class="page-title text-white">Contact us<span class="text-white">Keep in touch with us</span></h1>
        </div>
    </div>

    <div class="page-content pb-0">
        <div class="container mb-6">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="title mb-1">Contact Information</h2>
                    <p class="mb-3">Have a question about an order, a product, or anything else? Reach out — we're happy to help.</p>

                    <div class="row">
                        <div class="col-sm-7">
                            <div class="contact-info">
                                <h3>Get in Touch</h3>
                                <ul class="contact-list">
                                    <li>
                                        <i class="icon-map-marker"></i>
                                        Rahim Yar Khan, Punjab, Pakistan
                                    </li>
                                    <li>
                                        <i class="icon-phone"></i>
                                        <a href="tel:+923000000000">+92 300 0000000</a>
                                    </li>
                                    <li>
                                        <i class="icon-envelope"></i>
                                        <a href="mailto:info@store.com">info@store.com</a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-sm-5">
                            <div class="contact-info">
                                <h3>Store Hours</h3>
                                <ul class="contact-list">
                                    <li>
                                        <i class="icon-clock-o"></i>
                                        <span class="text-dark">Monday-Saturday</span> <br>10am-8pm
                                    </li>
                                    <li>
                                        <i class="icon-calendar"></i>
                                        <span class="text-dark">Sunday</span> <br>Closed
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <h2 class="title mb-1">Got Any Questions?</h2>
                    <p class="mb-2">Fill in the form below and our team will get back to you.</p>

                    <form action="#" class="contact-form mb-3" onsubmit="alert('Thanks! This demo form does not send messages yet.'); return false;">
                        <div class="row">
                            <div class="col-sm-6">
                                <label for="cname" class="sr-only">Name</label>
                                <input type="text" class="form-control" id="cname" placeholder="Name *" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="cemail" class="sr-only">Email</label>
                                <input type="email" class="form-control" id="cemail" placeholder="Email *" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <label for="cphone" class="sr-only">Phone</label>
                                <input type="tel" class="form-control" id="cphone" placeholder="Phone">
                            </div>
                            <div class="col-sm-6">
                                <label for="csubject" class="sr-only">Subject</label>
                                <input type="text" class="form-control" id="csubject" placeholder="Subject">
                            </div>
                        </div>

                        <label for="cmessage" class="sr-only">Message</label>
                        <textarea class="form-control" cols="30" rows="4" id="cmessage" required placeholder="Message *"></textarea>

                        <button type="submit" class="btn btn-outline-primary-2 btn-minwidth-sm">
                            <span>SUBMIT</span>
                            <i class="icon-long-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>