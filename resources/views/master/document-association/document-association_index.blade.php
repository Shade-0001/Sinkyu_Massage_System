<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.document-association.index')"
  />


  @if(session('error'))
    <div style="color: red;">{{ session('error') }}</div>
  @endif

  <table id="documentAssociationTable" class="table table-bordered">
    <thead>
      <tr>
        <th style="width: 30%;">文書カテゴリ</th>
        <th style="width: 70%;">文書</th>
      </tr>
    </thead>
    <tbody>
      @foreach($categories as $category)
        @php
          // このカテゴリに属するハードコード文書を取得
          $categoryDocuments = collect($fixedDocuments)->where('document_category', $category)->values();
          $docCount = $categoryDocuments->count();
        @endphp
        @if($docCount > 0)
          @foreach($categoryDocuments as $index => $document)
            <tr>
              @if($index === 0)
                <td rowspan="{{ $docCount }}">{{ $category }}</td>
              @endif
              <td class="fw-medium">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <div style="flex: 1;">
                    {{ $document['document_name'] }}
                  </div>
                  <div style="flex: 1;">
                    <form action="{{ route('master.document-association.associate', $document['id']) }}" method="POST" style="margin: 0;">
                      @csrf
                      <select name="document_id_2" style="width: 100%;" onchange="this.form.submit()">
                        <option value="">-- 選択 --</option>
                        @foreach($documents as $doc)
                          <option value="{{ $doc->id }}"
                            {{ isset($associations[$document['id']]) && $associations[$document['id']]->document_id_2 == $doc->id ? 'selected' : '' }}>
                            {{ $doc->document_name }}
                          </option>
                        @endforeach
                      </select>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          @endforeach
        @else
          <tr>
            <td class="fw-medium">{{ $category }}</td>
            <td style="color: #999;">（文書未登録）</td>
          </tr>
        @endif
      @endforeach
    </tbody>
  </table>

</x-app-layout>
