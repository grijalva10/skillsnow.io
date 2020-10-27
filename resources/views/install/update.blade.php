@extends('install.layout.master')
@section('title')
Update
@endsection

@section('update')

    <style>
        .main-col {
            display: none !important;
        }

        .hidden {
            display: none !important;
        }

        .update-messages {
            margin-top: 3rem;
        }

        .update-messages>p {
            margin-bottom: 1.5rem;
        }

        .update-messages>p>i {
            color: #673AB7;
            font-size: 2rem;
            margin-right: 1rem;
        }

        .message-overlay {
            position: absolute;
            height: 17rem;
            width: 100%;
            background-color: #fafafa;
            transform: translateY(0px);
            transition: 0.1s linear all;
        }
    </style>
    <div class="col-lg-4 col-lg-offset-4 mt-5">
        <div class="thankyou-box">
            <h2>
                Update Available {{ $updateVersion }} 🔥
            </h2>
            <p>
                This is the update wizard.
            </p>
            <form action="{{ url('install/update') }}" method="POST">
                <input autofocus="" class="form-control mt-2" name="password" placeholder="Enter the Admin Password to continue" style="margin-top: 1.5rem" type="password"/>
                <button class="btn btn-primary update-button" style="margin-top: 2rem;">
                    Update Now
                </button>
                @csrf
            </form>
            <div class="box error-msg">
                <div class="text-danger">
                    @if(Session::has('message'))
                    {{ Session::get('message') }}
                    @endif
                    @if($errors->any())
                    {{ implode('', $errors->all(':message')) }}
                    @endif
                </div>
            </div>
            <div class="box error-msg">
                <div class="text-danger">
                    @if(Session::has('success'))
                    {{ Session::get('success') }}
                    @endif
                    @if($errors->any())
                    {{ implode('', $errors->all(':success')) }}
                    @endif
                </div>
            </div>
            <div class="warning-msg hidden" style="margin-top: 1.5rem">
                <p class="text-danger">
                    Update process can take upto 30 seconds.
                </p>
                <p class="text-danger">
                    <strong>
                        DONOT
                    </strong>
                    close or reload this window.
                </p>
            </div>
        </div>
        <div class="update-messages">
            <div class="message-overlay">
            </div>
            <p>
                <i class="fa fa-check-circle">
                </i>
                <span>
                    Migrating new tables...
                </span>
            </p>
            <p>
                <i class="fa fa-check-circle">
                </i>
                <span>
                    Populating new settings...
                </span>
            </p>
            <p>
                <i class="fa fa-check-circle">
                </i>
                <span>
                    Setting API routes...
                </span>
            </p>
            <p>
                <i class="fa fa-check-circle">
                </i>
                <span>
                    Clearing junk files...
                </span>
            </p>
            <p>
                <i class="fa fa-check-circle">
                </i>
                <span>
                    Adding some magic beans...just a sec...
                </span>
            </p>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $(".update-button").on("click", function(e) {
            var button = $(e.currentTarget);
             button
                 .css("pointer-events", "none")
                 .data("loading-text", button.html())
                 .addClass("btn-loading")
                 .button("loading");

            $('.error-msg').remove();
            $('.warning-msg').removeClass("hidden");

           
            setTimeout(()=>{
                console.log("Exec timeout")
                let startTime = Date.now();
                let count = 30;
                let buffer = 0
                var msgShowInterval = setInterval(() => {
                    if (Date.now() - startTime > 8000) { // run only for 8 seconds 
                        clearInterval(msgShowInterval);
                         return;
                     }
                    console.log("Exec interval")
                    $('.message-overlay').css({
                        'transform':'translateY('+count+'px)',
                        'transition':'0.1s linear all'
                    });
                    buffer = buffer + 3
                    count = count + 30 + buffer;
                }, 1500);
            }, 2000)
            });
            $(this).attr('disabled', 'disabled');
        });
    </script>
    
@endsection
