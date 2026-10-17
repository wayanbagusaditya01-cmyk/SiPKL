@extends('layouts.portal')

@section('title', 'Pembina Saya')

@section('content')
<x-page-heading title="Pembina Saya" description="Pembina yang terhubung ke penempatan PKL-mu."/>
@if(!$student->placement)
    <section class="panel"><x-empty title="Pembina belum tersedia" description="Pembina akan muncul setelah penempatan ditetapkan."/></section>
@else
    <div class="two-column">
        @foreach([['label' => 'Pembina sekolah', 'mentor' => $student->placement->schoolMentor], ['label' => 'Pembina industri', 'mentor' => $student->placement->industryMentor]] as $item)
            <section class="panel"><span class="round-icon"><x-icon name="user"/></span><p class="muted">{{ $item['label'] }}</p>
                @if($item['mentor'])<h2>{{ $item['mentor']->user->name }}</h2><dl><dt>Email</dt><dd>{{ $item['mentor']->user->email }}</dd><dt>Telepon</dt><dd>{{ $item['mentor']->phone ?: 'Belum dicatat' }}</dd><dt>Jabatan / NIP</dt><dd>{{ $item['mentor']->position ?: ($item['mentor']->nip ?: 'Belum dicatat') }}</dd></dl>
                @else<h2>Belum ditetapkan</h2><p class="muted">Koordinator belum memilih {{ $item['label'] }}.</p>@endif
            </section>
        @endforeach
    </div>
@endif
@endsection