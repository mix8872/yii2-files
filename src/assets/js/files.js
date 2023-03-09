(function ($) {
    $(document).on('click', 'a.delete-attachment-file', function (e) {
        e.preventDefault();
        var that = $(this);
        var url = that.attr('href');
        var table = that.closest('.grid-view');
        var tr = that.closest('tr');
        if (confirm('Вы действительно хотите удалить элемент?')) {
            $.ajax({
                url: url,
                method: 'post',
                success: function (r) {
                    tr.remove();
                    if (!$('tbody tr', table).length) {
                        table.remove();
                    }
                    return false;
                },
            });
        }
    });

    $('a.lightbox').magnificPopup({
        type: 'image',
    });

    $('.js-yiiFile-edit').on('click', function (e) {
        e.preventDefault();
        var modal = $('<div>', {
            id: 'file-edit-modal',
            class: 'modal fade',
            tabindex: -1,
            role: 'dialog',
            'aria-hidden': 'true',
            style: 'display: none'
        });
        $.ajax({
            url: $(this).attr('href'),
            success: function (r) {
                modal.append(r);
                $(document.body).append(modal);
                modal.modal();
                modal.on('hide.bs.modal', function () {
                    $(this).remove();
                });
            }
        });
    });

    $(document).on('click', '.file-edit-submit', function (e) {
        e.preventDefault();
        var $this = $(this),
            url = $this.data('url'),
            fields = $this.parents('.modal-content').find('input.form-control'),
            data = {};
        fields.each(function (key, item) {
            data[$(item).attr('name')] = $(item).val();
        });

        $this.removeClass('btn-primary').html('<i class="fa fa-spin fa-square"></i>');

        $.ajax({
            url: url,
            data: data,
            method: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response) {
                    $.toast({
                        text: "Свойства файла успешно сохранены",
                        position: "top-center",
                        icon: "success",
                        hideAfter: 2000,
                        stack: 15
                    });
                    $this.addClass('btn-success').html('Сохранено');
                } else {
                    $.toast({
                        text: "Ошибка сохранения",
                        position: "top-center",
                        icon: "error",
                        hideAfter: 2000,
                        stack: 15
                    });
                    $this.addClass('btn-primary').html('Сохранить');
                }
            }
        });
    });

}(jQuery));
