@include('errors.layout', ['code' => 403, 'title' => __('Not allowed'), 'message' => $exception->getMessage() ?: __('You don\'t have access to this page.')])
