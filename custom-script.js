document.addEventListener('DOMContentLoaded', function() {
    var page = 1;
    var loading = false;

    function loadBooks() {
        if (!loading) {
            loading = true;
            var xhr = new XMLHttpRequest();

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = xhr.responseText;

                    if (response !== 'No books found.') {
                        document.getElementById('custom-books-list').innerHTML += response;
                        page++;
                    } else {
                        document.getElementById('custom-books-pagination').innerHTML = 'No more books to load.';
                    }
                    loading = false;
                }
            };

            xhr.open('POST', ajax_object.ajax_url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=custom_books_pagination&page=' + page + '&posts_per_page=' + ajax_object.posts_per_page);
        }
    }

    loadBooks();

    window.addEventListener('scroll', function() {
        if (window.scrollY + window.innerHeight >= document.getElementById('custom-books-list-container').offsetHeight - 100) {
            loadBooks();
        }
    });
});
