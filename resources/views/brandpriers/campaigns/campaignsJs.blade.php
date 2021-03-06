<script type="text/javascript">


    var table = $('.data-table').DataTable({
        dom: 'Bfrtip',
        "columnDefs": [
            {"width": "50px", "targets": 8},
            {"targets": 0, "className": "text-center",}
        ],
        processing: true,
        responsive: true,
        serverSide: true,
        data: {
            "brand_id": $('#brand_id').val()
        },
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
        ajax: {
            url: "{{ route('campaignsDatable', app()->getLocale()) }}",
            type: "get",
            data: {
                "brand_id": $('#brand_id').val(),
                "package_id": $('#package_id').val(),
            }
        },
        columns: [
            {data: 'DT_RowIndex', title: 'ID'},
            {data: 'name', title: 'Name'},
            {
                data: 'id', title: 'Count Bubbles', "mRender": function (data, type, row) {
                    return '<span class="font-weight-bold">'  + row.number_bubbles + ' </span>'

                }
            },
            {
                data: 'id', title: 'Count Bubbles Hooked', "mRender": function (data, type, row) {
                    return '<span class="font-weight-bold">' + row.number_bubbles_hooked + ' </span>'

                }
            },
            {
                data: 'id', title: 'Date', "mRender": function (data, type, row) {
                    return '<span class="font-weight-bold text-success">' + row.start_date + ' - ' + row.end_date + ' </span>'

                }
            },

            {
                data: 'id', title: 'Time', "mRender": function (data, type, row) {
                    return '<span class="font-weight-bold text-info">' + row.from_time + ' - ' + row.to_time + ' </span>'

                }
            },


            {
                data: 'id', title: 'Status', "mRender": function (data, type, row) {
                    if (row.available == 'False') {
                        return '<span class="label font-weight-bold label-lg  label-light-danger label-inline">' + row.available + '</span>'
                    } else if (row.available == 'True') {
                        return '<span class="label font-weight-bold label-lg  label-light-success label-inline">' + row.available + '</span>'
                    }
                }
            },
            {
                data: 'id', title: 'Services', "mRender": function (data, type, row) {
                    var gift = '<a href="/{{app()->getLocale()}}/gifts/' + row.id + '"  class="btn btn-sm btn-clean btn-icon action-btn" id="' + row.id + '" data-toggle="tooltip" data-placement="bottom" title="Gift"><i class="fa fa-gift"></i></a>'
                    return gift;
                }

            },

            {
                data: 'id',title: 'Actions', "mRender": function (data, type, row) {
                    var edit = '<a href="#" class="btn btn-sm btn-clean btn-icon edit-btn action-btn" id="' + row.id + '"  data-toggle="tooltip" data-placement="bottom" title="View & Edit"><i class="fas fa-edit" ></i></a>';
                    var remove = '<a href="#" class="btn btn-sm btn-clean btn-icon action-btn remove-btn"  id="' + row.id + '" data-toggle="tooltip" data-placement="bottom" title="Remove"><i class="far fa-trash-alt" ></i></a>';
                    return edit + remove;

                }
            }
        ]


    });

    $('#add').on('click', function () {
        var brand_id = $('#brand_id').val()
        var package_id = $('#package_id').val()
        $.ajax({
            url: '/{{app()->getLocale()}}/campaign/' + brand_id + '/' + package_id + '/create',
            method: 'get',
            success: function (data) {
                $('.modal-body').html(data);
                $('.modal-title').text('Add Campaign');
                $('#modal').modal('show');

                $('#userForm').submit(function (e) {
                    e.preventDefault();
                    var form = $(this);
                    var url = form.attr('action');
                    var bulk_ids = '';
                    var select_id = '';
                    var error_html_select = '';
                    $("#submitBtn").attr("disabled", true);
                    $(".div_bulk").each(function (index) {
                        select_id = $(this).find(" select").children("option:selected").val();
                        if (select_id == '') {
                            error_html_select += '<div class="alert alert-danger">Please  Select All Bulks  </div>';
                        } else {
                            bulk_ids += select_id + ",";
                        }

                    });
                    if (error_html_select != "") {
                        $("#submitBtn").attr("disabled", false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            html: error_html_select,
                        })
                    } else {
                        $("#bulk_ids").val(bulk_ids);
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
                                    $("#submitBtn").attr("disabled", false);
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
                    }
                });
            }
        });
    });
    $(document).on('click', '.edit-btn', function () {
        var id = $(this).attr('id');
        $.ajax({
            url: '/{{app()->getLocale()}}/campaigns/' + id + '/edit',
            type: 'get',
            success: function (data) {
                $('.modal-body').html(data);
                $('.modal-title').text('Edit Campaign');
                $('#modal').modal('show');

                $('#userForm').submit(function (e) {
                    e.preventDefault();
                    $(".btn").attr("disabled", true);
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
                                $(".btn").attr("disabled", false);
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
    $(document).on('click', '.remove-btn', function () {

        var id = $(this).attr('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    url: '/{{(app()->getLocale())}}/campaigns/' + id,
                    method: 'delete',
                    success: function (data) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Your Campaign has been removed',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        table.ajax.reload();
                    }
                });
            }
        });

    });

</script>
