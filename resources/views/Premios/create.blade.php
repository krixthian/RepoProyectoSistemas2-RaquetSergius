@extends('layouts.app')

@section('content')
<h1 style="color: #fff; margin-bottom: 20px;">Crear nuevo Premio</h1>

<form action="{{ route('premios.store') }}" method="POST" 
      style="background: #222; padding: 20px; border: 2px solid #007bff; border-radius: 10px; color: #fff; max-width: 400px">
    @csrf
    <label>Nombre:<input name="nombre" required style="width: 100%; margin-bottom: 15px; padding: 10px; background: #333; color: #fff; border: 1px solid #007bff; border-radius: 5px;"></label>
    <label>Puntos Requeridos:<input name="puntos_requeridos" type="number" required style="width: 100%; margin-bottom: 15px; padding: 10px; background: #333; color: #fff; border: 1px solid #007bff; border-radius: 5px;"></label>
    <label>Tipo:<input name="tipo" required style="width: 100%; margin-bottom: 15px; padding: 10px; background: #333; color: #fff; border: 1px solid #007bff; border-radius: 5px;"></label>
    <label>Activo:<input name="activo" type="checkbox" value="1" style="margin-bottom: 15px; transform: scale(1.5);"></label>

    <br>
    <button type="submit" style="padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; font-weight: bold; cursor: pointer">
        Crear
    </button>
</form>
@endsection
