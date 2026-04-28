import React, { useState, useEffect } from 'react';
import { Users, TrendingUp, UserMinus, GraduationCap, Clock, Loader2 } from 'lucide-react';
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
  PieChart, Pie, Cell
} from 'recharts';

// Cores para o gráfico de pizza
const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'];

export default function DashboardRH() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Efeito para carregar os dados ao iniciar a tela
  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        // Substitua pela URL real da sua API: fetch('https://api.prf.gov.br/rh/dashboard')
        // Simulando a chamada da API
        const response = await mockApiCall();
        setData(response);
      } catch (err) {
        setError('Erro ao carregar os dados do painel.');
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50">
        <Loader2 className="w-12 h-12 text-blue-600 animate-spin" />
        <p className="mt-4 text-gray-600 font-medium">Carregando painel...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-50">
        <p className="text-red-500 font-medium">{error}</p>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      <h1 className="text-2xl font-bold text-gray-800 mb-6">Painel de Recursos Humanos - PRF</h1>

      {/* Grid de Indicadores (Cards) */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <Card 
          title="Total de Servidores" 
          value={data.indicadores.totalServidores} 
          icon={<Users className="text-blue-500" />} 
        />
        <Card 
          title="Crescimento Percentual" 
          value={data.indicadores.crescimentoPercentual} 
          icon={<TrendingUp className="text-green-500" />} 
        />
        <Card 
          title="Próximos da Aposentadoria" 
          value={data.indicadores.proximosAposentadoria} 
          icon={<UserMinus className="text-orange-500" />} 
        />
        <Card 
          title="Capacitações no Ano" 
          value={data.indicadores.capacitacoesAno} 
          icon={<GraduationCap className="text-purple-500" />} 
        />
        <Card 
          title="Crescimento de Capacitações" 
          value={data.indicadores.crescimentoCapacitacoes} 
          icon={<TrendingUp className="text-teal-500" />} 
        />
        <Card 
          title="Tempo Médio de Serviço" 
          value={data.indicadores.tempoMedioServico} 
          icon={<Clock className="text-red-500" />} 
        />
      </div>

      {/* Grid de Gráficos */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {/* Gráfico de Barras: Servidores por Unidade */}
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h2 className="text-lg font-semibold text-gray-700 mb-4">Servidores por Unidade</h2>
          <div className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data.graficos.servidoresPorUnidade}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="unidade" />
                <YAxis />
                <Tooltip cursor={{fill: 'transparent'}} />
                <Legend />
                <Bar dataKey="quantidade" fill="#3b82f6" name="Qtd de Servidores" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Gráfico de Pizza: Distribuição por Status */}
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h2 className="text-lg font-semibold text-gray-700 mb-4">Distribuição por Status</h2>
          <div className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={data.graficos.distribuicaoPorStatus}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={5}
                  dataKey="quantidade"
                  nameKey="status"
                  label={({ status, percent }) => `${status} ${(percent * 100).toFixed(0)}%`}
                >
                  {data.graficos.distribuicaoPorStatus.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </div>

      </div>
    </div>
  );
}

// Componente Auxiliar para os Cards
function Card({ title, value, icon }) {
  return (
    <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
      <div>
        <p className="text-sm font-medium text-gray-500 mb-1">{title}</p>
        <p className="text-2xl font-bold text-gray-900">{value}</p>
      </div>
      <div className="p-3 bg-gray-50 rounded-full">
        {icon}
      </div>
    </div>
  );
}

// ============================================================================
// MOCK DA API - Simula a estrutura exata que o backend deve retornar
// (Sem necessidade de cálculos no frontend)
// ============================================================================
const mockApiCall = () => {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve({
        indicadores: {
          totalServidores: "12.450",
          crescimentoPercentual: "+1.2%",
          proximosAposentadoria: "840",
          capacitacoesAno: "3.210",
          crescimentoCapacitacoes: "+15.4%",
          tempoMedioServico: "14 anos"
        },
        graficos: {
          servidoresPorUnidade: [
            { unidade: "SP", quantidade: 2100 },
            { unidade: "RJ", quantidade: 1850 },
            { unidade: "MG", quantidade: 1600 },
            { unidade: "PR", quantidade: 1450 },
            { unidade: "RS", quantidade: 1200 },
            { unidade: "DF", quantidade: 950 }
          ],
          distribuicaoPorStatus: [
            { status: "Ativos", quantidade: 12450 },
            { status: "Afastados", quantidade: 320 },
            { status: "Cedidos", quantidade: 150 },
            { status: "Em Treinamento", quantidade: 480 }
          ]
        }
      });
    }, 1500); // Simula 1.5s de delay da rede
  });
};