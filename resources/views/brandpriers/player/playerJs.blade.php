<script type="text/javascript">


    var table = $('.data-table').DataTable({

        dom: 'Bfrtip',
        "columnDefs": [


            /*//{"width": "160px", "targets": 7},
            {"width": "50px", "targets": 4},*/
        ],
        processing: true,
        serverSide: true,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            ['10 rows', '25 rows', '50 rows', '100 rows', 'Show all']
        ],
        buttons: [
            {'extend': 'pageLength'},
            {
                text: 'Reload',
                action: function (e, dt, node, config) {
                    dt.ajax.reload();
                }
            },
            {'extend': 'excel'},
            {'extend': 'print'},
            {'extend': 'pdf'}
        ],
        ajax: "{{ route('playersDatable', app()->getLocale()) }}",
        columns: [
            {data: 'DT_RowIndex', title: 'ID'},
            {
                title: 'Name', "mRender": function (data, type, row) {
                    return row.first_name +' '+ row.last_name;

                }
            },
            {data: 'email', title: 'Email'},
             {
                title: 'Level', "mRender": function (data, type, row) {row.level
                    var level_name= row.level + ' <br>'+ '<span style="font-weight: bold;color: #6da5a2;"> '+row.lvl_pts+' </span>'+ '/ ' +'<span style="font-weight: bold;"> '+row.level_points+' </span>';
                    return level_name;

                }
            },
            {
                title: 'Tank', "mRender": function (data, type, row) {
                    var tank_name= row.tank + ' <br>'+ '<span style="font-weight: bold;color: #6da5a2;"> '+row.tank_points+' </span>'+ '/ ' +'<span style="font-weight: bold;"> '+row.tank_size+' </span>';
                    return tank_name;

                }
            },
            {data: 'extraTank', title: 'Extra Tank'},


            /*{
                title: 'logo', "mRender": function (data, type, row) {
                    var imgeUrl = ' asset('images/') ';
                    if (row.school_logo != '') {
                        return '<img src="' + imgeUrl + '/' + row.name_en + '/' + row.school_logo + '" class="avatar" width="50" height="50"/>';
                    } else
                        return "Not Found Logo";

                }
            },
            {
                data: 'status', title: 'Status', "mRender": function (data, type, row) {
                    if (row.status == 'False') {
                        return '<span class="label font-weight-bold label-lg  label-light-danger label-inline">' + row.status + '</span>'
                    } else if (row.status == 'True') {
                        return '<span class="label font-weight-bold label-lg  label-light-success label-inline">' + row.status + '</span>'
                    }
                }
            },*/


            /* {
                 title: 'Services', "mRender": function (data, type, row) {
                     var gallery = '<a href="#" class="btn btn-sm btn-clean btn-icon action-btn gallerySchool" id="' + row.id + '"  title="School gallery"><i class="fa fa-file-image"></i></a>';
                     var transportation = '<a href="/schools/transportation/' + row.id + '" target="_blank" class="btn btn-sm btn-clean btn-icon action-btn" id="' + row.id + '" title="Transportation"><i class="fa fa-bus"></i></a>';
                     var premiums = '<a href="/schools/premium/' + row.id + '" target="_blank" class="btn btn-sm btn-clean btn-icon action-btn" id="' + row.id + '"   title="Premiums"><i class="fa fa-credit-card" ></i></a>';
                     var news = '<a href="/schools/news/' + row.id + '" target="_blank" class="btn btn-sm btn-clean btn-icon action-btn" title="News"><i class="fa fa-newspaper" ></i></a>';
                     var notes = '<a href="/schools/note/' + row.id + '" target="_blank" class="btn btn-sm btn-clean btn-icon action-btn" title="Notes"><i class="fa fa-sticky-note""></i></a>';
                     return gallery + transportation + premiums + news + notes;

                 }

             },*/
            {
                title: 'Actions', "mRender": function (data, type, row) {
                    var edit = '<a href="#" class="btn btn-sm btn-clean btn-icon edit-user-btn action-btn" id="' + row.id + '"  data-toggle="tooltip" data-placement="bottom" title="View & Edit"><i class="fas fa-edit" style="color: #3699ff"></i></a>';
                    var remove = '<a href="#" class="btn btn-sm btn-clean btn-icon action-btn remove-School-btn"  id="' + row.id + '" data-toggle="tooltip" data-placement="bottom" title="Remove"><i class="far fa-trash-alt" style="color: #f64e60"></i></a>';
                    return edit + remove;
                    /*var show = '<button data-toggle="modal" data-target="#productModal" class="btn btn-success  showM" id="' + row.id + '"><i class="fa fa-eye"></i></button >';
                     return show;*/
                }
            }
        ]
    });


    $('#addUser').on('click', function () {

        $.ajax({
            url: '{{ route('users.create', app()->getLocale()) }}',
            method: 'get',
            success: function (data) {
                $('.modal-body').html(data);
                $('.modal-title').text('Add User');
                $('#modal').modal('show');

                $('#userForm').submit(function (e) {
                    e.preventDefault();
                    var form = $(this);
                    var url = form.attr('action');
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: new FormData(this),
                        dataType: "json",
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function (data) {

                            if (data.status === 422) {
                                console.log(data);
                                var error_html = '';

                                for (let value of Object.values(data.errors)) {
                                    error_html += '<div class="alert alert-danger">' + value + '</div>';
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    html: error_html,
                                })
                            } else {


                                Swal.fire({
                                    icon: 'success',
                                    title: data.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                table.ajax.reload();
                                $('#modal').modal('hide');
                            }
                        }
                    });

                });
            }
        });
    });
    $(document).on('click', '.edit-user-btn', function () {
        var id = $(this).attr('id');
        $.ajax({
            url: '/{{app()->getLocale()}}/users/' + id + '/edit',
            type: 'get',
            success: function (data) {
                $('.modal-body').html(data);
                $('.modal-title').text('Edit User');
                $('#modal').modal('show');

                $('#userForm').submit(function (e) {
                    e.preventDefault();
                    var form = $(this);
                    var url = form.attr('action');
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: new FormData(this),
                        dataType: "json",
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function (data) {

                            if (data.status === 422) {
                                console.log(data);
                                var error_html = '';

                                for (let value of Object.values(data.errors)) {
                                    error_html += '<div class="alert alert-danger">' + value + '</div>';
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    html: error_html,
                                })
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: data.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                });

                                table.ajax.reload();
                                $('#modal').modal('hide');
                            }
                        }
                    });

                });
            }
        });
    });

</script>