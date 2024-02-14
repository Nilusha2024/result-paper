<x-app-layout>
    <br>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.css">
    ​

    <head>
        <style>
            .bg-wrapper {
                color: #000;
                overflow-x: hidden;
                height: 100%;
                background-image: url("https://images.pexels.com/photos/802861/pexels-photo-802861.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1");
                background-repeat: no-repeat;
                background-size: cover;
                /* margin-top: -3rem; */
            }

            .card {
                padding: 30px 40px;
                margin-top: 60px;
                margin-bottom: 60px;
                border: none !important;
                box-shadow: 0 6px 12px 0 rgba(0, 0, 0, 0.2)
            }

            .blue-text {
                color: #00BCD4
            }

            .form-control-label {
                margin-bottom: 0
            }

            input,
            textarea,
            button {
                padding: 8px 15px;
                border-radius: 5px !important;
                margin: 5px 0px;
                box-sizing: border-box;
                border: 1px solid #ccc;
                font-size: 18px !important;
                font-weight: 300
            }

            input:focus,
            textarea:focus {
                -moz-box-shadow: none !important;
                -webkit-box-shadow: none !important;
                box-shadow: none !important;
                border: 1px solid #00BCD4;
                outline-width: 0;
                font-weight: 400
            }

            .btn-block {
                text-transform: uppercase;
                font-size: 15px !important;
                font-weight: 400;
                height: 43px;
                cursor: pointer
            }

            .btn-block:hover {
                color: #ffffff !important
            }

            button:focus {
                -moz-box-shadow: none !important;
                -webkit-box-shadow: none !important;
                box-shadow: none !important;
                outline-width: 0
            }
        </style>
        <script>
            function redirectToUploadPage() {
                window.location.href = 'http://localhost/result-paper/public/bar_form';
            }
        </script>
    </head>

    <!-- wrapper -->
        <div class="bg-wrapper">
            <br><br><br>​
            <div class="container-fluid px-1 py-5 mx-auto">
                <div class="row d-flex justify-content-center">
                    <div class="col-xl-7 col-lg-8 col-md-9 col-11 text-center">
                        <h1>File Upload</h1>
                        <div class="row">
                            <!-- First Form -->
                            ​
                            <!-- Second Form -->

                            <!-- Therd Form -->
                            <div class="col-md-12">
                                <!-- Use col-md-6 to make it occupy half of the row on medium devices and larger -->
                                <div class="card"
                                    style="background-color: rgba(255, 255, 255, 0.6); border-radius: 10px;">

                                    <form class="form-card" action="{{ route('BarFormUpload') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="row justify-content-between text-left">
                                            <div class="form-group col-sm-6 flex-column d-flex"></div>
                                            <input class="form-control form-control-lg col-md-8 mx-auto" id="BarForm"
                                                name="BarForm[]" type="file" multiple required>
                                            <button type="submit" class="btn-block btn-success col-md-8 mx-auto">Upload
                                                Files</button>
                                        </div>
                                        @if (session('BarForm_upload_status'))
                                        @if (session('BarForm_upload_status') == 1)
                                        <p style="color: green;">Upload successful</p>
                                        @else
                                        <p style="color: red;">Upload failed</p>
                                        @endif
                                        @endif

                                        <div class="row justify-content-end">
                                            <div class="form-group col-sm-6"></div>
                                        </div>
                                    </form>

                                </div>
                            </div>​
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
</x-app-layout>